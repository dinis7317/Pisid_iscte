import time
import mysql.connector
import pymongo
from bson.objectid import ObjectId
import paho.mqtt.client as mqtt
import json

BROKER = "broker.hivemq.com"
GRUPO = 21
TOPIC_MIGRACAO = f"pisid_migration_{GRUPO}"
ID_JOGO_ATUAL = None # Será preenchido no arranque

mongo_client = pymongo.MongoClient("mongodb://localhost:27017/")
db_mongo = mongo_client["pisid"]
colecao_sensores = db_mongo["sensores"]

def ligar_mysql():
    return mysql.connector.connect(
        host="172.20.10.2",      # IP do Mac
        user="pc1",               # O utilizador que acabaste de criar
        password="1234",          # A password que definiste no SQL acima
        database="pisid_maze",
        auth_plugin='mysql_native_password' # Isto ajuda a evitar erros de versão
    )
def obter_jogo_ativo(cursor):
    cursor.execute("""
        SELECT id_jogo
        FROM jogo
        WHERE status = 'ativo'
        ORDER BY id_jogo DESC
        LIMIT 1
    """)
    res = cursor.fetchone()
    return res[0] if res else None

def atualizar_ultimo_id_migrado(cursor, conn, novo_id):
    cursor.execute("UPDATE Controlemigracao SET ultimo_id_mongo = %s", (str(novo_id),))
    conn.commit()


# --- LÓGICA DE INSERÇÃO ---
def processar_e_inserir(data, cursor, conn):
    global ID_JOGO_ATUAL
    tipo = data.get('tipo')
    mongo_id = data.get('_id')
    inserido = False

    novo_jogo = obter_jogo_ativo(cursor)
    if novo_jogo is not None:
        ID_JOGO_ATUAL = novo_jogo

    if ID_JOGO_ATUAL is None:
        print("Sem jogo ativo.")
        return False

    try:
        if tipo == 'Temperature':
            sql = "INSERT INTO temperatura (Hora, Temperatura, id_jogo) VALUES (%s, %s, %s)"
            cursor.execute(sql, (data.get('Hour'), str(data.get('Temperature')), ID_JOGO_ATUAL))
            inserido = True

        elif tipo == 'Sound':
            sql = "INSERT INTO som (Hora, Som, id_jogo) VALUES (%s, %s, %s)"
            cursor.execute(sql, (data.get('Hour'), str(data.get('Sound')), ID_JOGO_ATUAL))
            inserido = True

        elif tipo == 'Movement':
            sql = "INSERT INTO medicoespassagens (Hora, SalaOrigem, SalaDestino, Marsami, Status, id_jogo) VALUES (%s, %s, %s, %s, %s, %s)"
            val = (
                data.get('Hour'),
                data.get('RoomOrigin'),
                data.get('RoomDestiny'),
                data.get('Marsami'),
                data.get('Status'),
                ID_JOGO_ATUAL
            )
            cursor.execute(sql, val)
            inserido = True

        if inserido:
            conn.commit()
            if mongo_id:
                atualizar_ultimo_id_migrado(cursor, conn, mongo_id)

    except mysql.connector.Error as err:
        # Se for o erro 1644 (o erro do Trigger), apenas ignoramos e continuamos
        if err.errno == 1644:
            print(f"⚠️ Dado ignorado pelo MySQL: {err.msg}")
            # Atualizamos o ID migrado mesmo assim, para não ficar preso neste dado "mau"
            if mongo_id:
                atualizar_ultimo_id_migrado(cursor, conn, mongo_id)
        else:
            print(f"❌ Erro de Base de Dados: {err}")
        return False

    return inserido

def on_message(client, userdata, msg):
    try:
        data = json.loads(msg.payload.decode())
        conn = ligar_mysql()
        cursor = conn.cursor()
        if processar_e_inserir(data, cursor, conn):
            print(f"Migrado via MQTT: {data.get('tipo')} | Jogo: {ID_JOGO_ATUAL}")
        cursor.close()
        conn.close()
    except Exception as e:
        print(f"Erro no processamento MQTT: {e}")

if __name__ == "__main__":
    mysql_conn = ligar_mysql()
    cursor_mysql = mysql_conn.cursor()

    # obter jogo ativo inicial
    ID_JOGO_ATUAL = obter_jogo_ativo(cursor_mysql)
    print(f"Jogo ativo inicial: {ID_JOGO_ATUAL}")


    # 2. RECUPERAÇÃO DE FALHAS
    print("Verificando dados perdidos no MongoDB...")
    cursor_mysql.execute("SELECT ultimo_id_mongo FROM Controlemigracao LIMIT 1")
    res = cursor_mysql.fetchone()
    ultimo_id = ObjectId(res[0]) if res and res[0] else None

    query = {'_id': {'$gt': ultimo_id}} if ultimo_id else {}
    perdidos = colecao_sensores.find(query).sort('_id', 1)

    for doc in perdidos:
        processar_e_inserir(doc, cursor_mysql, mysql_conn)
        print(f"Recuperado do Mongo para Jogo {ID_JOGO_ATUAL}")

    cursor_mysql.close()
    mysql_conn.close()

    # 3. MODO TEMPO REAL
    print("Sistema sincronizado. A aguardar MQTT...")
    try:
        client = mqtt.Client(mqtt.CallbackAPIVersion.VERSION1)
    except AttributeError:
        client = mqtt.Client()

    client.on_message = on_message
    client.connect(BROKER, 1883)
    client.subscribe(TOPIC_MIGRACAO)
    client.loop_forever()