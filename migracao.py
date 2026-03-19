import mysql.connector
import pymongo
import time
from bson.objectid import ObjectId

mongo_client = pymongo.MongoClient("mongodb://localhost:27017/")
db_mongo = mongo_client["pisid"]
colecao_sensores = db_mongo["sensores"]

def ligar_mysql():
    return mysql.connector.connect(
        host="127.0.0.1",
        port=3306,
        user="root",
        password="dinissilva2004", 
        database="pisid_maze"
    )

def obter_ultimo_id_migrado(cursor):
    cursor.execute("SELECT ultimo_id_mongo FROM Controlemigracao LIMIT 1")
    resultado = cursor.fetchone()
    if resultado and resultado[0] is not None:
        try: return ObjectId(resultado[0])
        except: return None
    return None

def atualizar_ultimo_id_migrado(cursor, conn, novo_id):
    cursor.execute("UPDATE Controlemigracao SET ultimo_id_mongo = %s", (str(novo_id),))
    conn.commit()

def iniciar_migracao():
    mysql_conn = ligar_mysql()
    cursor_mysql = mysql_conn.cursor()
    
    print("Migração em curso")
    
    while True:
        try:
            ultimo_id = obter_ultimo_id_migrado(cursor_mysql)
            query = {'_id': {'$gt': ultimo_id}} if ultimo_id else {}
            
            #Lê documentos novos do mongo
            novos_documentos = colecao_sensores.find(query).sort('_id', 1)
            
            for doc in novos_documentos:
                tipo = doc.get('tipo')
                inserido = False
                
                if tipo == 'Temperature':
                    valor_num = float(doc.get('Temperature', 0))
                    if valor_num < -50.0 or valor_num > 100.0:
                        print(f"valor fora dos limites: {valor_num} ignorado")
                    else:
                        sql = "INSERT INTO Temperatura (Hora, Temperatura) VALUES (%s, %s)"
                        cursor_mysql.execute(sql, (doc.get('Hour'), str(valor_num)))
                        inserido = True

                elif tipo == 'Sound':
                    valor_som = doc.get('Sound', 0)
                    sql = "INSERT INTO Som (Hora, Som) VALUES (%s, %s)"
                    cursor_mysql.execute(sql, (doc.get('Hour'), str(valor_som)))
                    inserido = True

                elif tipo == 'Movement':
                    sql = "INSERT INTO Medicoespassagens (Hora, SalaOrigem, SalaDestino, Marsami, Status) VALUES (%s, %s, %s, %s, %s)"
                    val = (doc.get('Hour'), doc.get('RoomOrigin'), doc.get('RoomDestiny'), doc.get('Marsami'), doc.get('Status'))
                    cursor_mysql.execute(sql, val)
                    inserido = True

                mysql_conn.commit()
                atualizar_ultimo_id_migrado(cursor_mysql, mysql_conn, doc['_id'])
                if inserido:
                    print(f"Migrado: {tipo} | ID: {doc['_id']}")
            
            time.sleep(1)
            
        except Exception as e:
            print(f"Erro: {e}")
            time.sleep(5)

if __name__ == "__main__":
    iniciar_migracao()