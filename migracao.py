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
        host="127.0.0.1",
        user="root",
        password="",
        database="pisid_maze"
    )

def atualizar_ultimo_id_migrado(cursor, conn, novo_id):
    cursor.execute("UPDATE Controlemigracao SET ultimo_id_mongo = %s", (str(novo_id),))
    conn.commit()

# --- LÓGICA DE INSERÇÃO CORRIGIDA ---
def processar_e_inserir(data, cursor, conn):
    global ID_JOGO_ATUAL
    tipo = data.get('tipo')
    mongo_id = data.get('_id')
    inserido = False

    # Se por acaso o ID_JOGO_ATUAL estiver vazio, tentamos recuperar o último jogo
    if ID_JOGO_ATUAL is None:
        cursor.execute("SELECT id_jogo FROM Jogo ORDER BY id_jogo DESC LIMIT 1")
        res = cursor.fetchone()
        ID_JOGO_ATUAL = res[0] if res else 1

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
        conn.commit() # Importante fazer commit para cada inserção
        if mongo_id:
            atualizar_ultimo_id_migrado(cursor, conn, mongo_id)
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

    # 1. CRIAR NOVO JOGO
    print("A iniciar nova simulação...")
    descricao_jogo = f"Simulação Grupo 21 - {time.strftime('%Y-%m-%d %H:%M:%S')}"
    cursor_mysql.execute("INSERT INTO Jogo (DataInicio, Descricao) VALUES (NOW(), %s)", (descricao_jogo,))
    mysql_conn.commit()
    ID_JOGO_ATUAL = cursor_mysql.lastrowid
    print(f"ID do Jogo Criado: {ID_JOGO_ATUAL}")

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