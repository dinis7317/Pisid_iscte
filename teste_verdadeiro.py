import paho.mqtt.client as mqtt
from pymongo import MongoClient
import json
from datetime import datetime

mongo = MongoClient("mongodb://localhost:27017/")
db = mongo["pisid"]

BROKER = "broker.emqx.io"
GRUPO  = 21

TEMP_MIN, TEMP_MAX = 0, 50
SOM_MIN,  SOM_MAX  = 0, 50

def validar_hora(hora_str):
    try:
        datetime.strptime(hora_str, "%Y-%m-%d %H:%M:%S.%f")
        return True
    except (ValueError, TypeError):
        return False

def on_message(client, userdata, msg):
    raw = msg.payload.decode()
    print(msg.topic, raw)

    try:
        data = json.loads(raw)
    except json.JSONDecodeError:
        print(f"JSON inválido: {raw}")
        return

    data["_recebido_em"] = datetime.now()

    #Temperatura
    if msg.topic == f"pisid_mazetemp_{GRUPO}":
        valor = data.get("Temperature")
        hora  = data.get("Hour", "")

        if valor is None:
            return

        #Hora inválida
        if not validar_hora(hora):
            print(f"Hora inválida: {hora} — ignorado")
            db.outliers.insert_one({**data, "tipo": "Temperature", "motivo": "hora_invalida"})
            return

        #Valor fora dos limites
        if float(valor) < TEMP_MIN or float(valor) > TEMP_MAX:
            print(f"temperatura fora dos limites: {valor} — ignorado")
            db.outliers.insert_one({**data, "tipo": "Temperature", "motivo": "valor_fora_limites"})
            return

        db.sensores.insert_one({**data, "tipo": "Temperature"})
        print("Temperatura guardada")

    #som
    elif msg.topic == f"pisid_mazesound_{GRUPO}":
        valor = data.get("Sound")
        hora  = data.get("Hour", "")

        if valor is None:
            return

        if not validar_hora(hora):
            print(f"Hora inválida: {hora} — ignorado")
            db.outliers.insert_one({**data, "tipo": "Sound", "motivo": "hora_invalida"})
            return

        if float(valor) < SOM_MIN or float(valor) > SOM_MAX:
            print(f"som fora dos limites: {valor} — ignorado")
            db.outliers.insert_one({**data, "tipo": "Sound", "motivo": "valor_fora_limites"})
            return

        db.sensores.insert_one({**data, "tipo": "Sound"})
        print("Som guardado")

    #movimentos
    elif msg.topic == f"pisid_mazemov_{GRUPO}":
        marsami  = data.get("Marsami")
        origem   = data.get("RoomOrigin")
        destino  = data.get("RoomDestiny")
        status   = data.get("Status")

        if marsami is None:
            return

        # Status 2 = cansado (imobilizado)
        if status == 2:
            print(f"cansado: Marsami {marsami} imobilizado")

        db.sensores.insert_one({**data, "tipo": "Movement"})
        print("Movimento guardado")

def on_connect(client, userdata, flags, rc):
    if rc == 0:
        print("Ouvinte MQTT ligado e pronto! Podes correr o mazerun.")
        client.subscribe(f"pisid_mazetemp_{GRUPO}")
        client.subscribe(f"pisid_mazesound_{GRUPO}")
        client.subscribe(f"pisid_mazemov_{GRUPO}")
    else:
        print(f"[ERRO] rc={rc}")

def on_disconnect(client, userdata, rc):
    if rc != 0:
        print("[Aviso] Desligado, a reconectar...")

client = mqtt.Client(mqtt.CallbackAPIVersion.VERSION1)
client.on_connect    = on_connect
client.on_message    = on_message
client.on_disconnect = on_disconnect

client.connect(BROKER, 1883, keepalive=60)
client.loop_forever()