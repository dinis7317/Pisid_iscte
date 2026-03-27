import paho.mqtt.client as mqtt
from pymongo import MongoClient
import json
from datetime import datetime

mongo = MongoClient("mongodb://localhost:27017/")
db = mongo["pisid"]

BROKER = "broker.emqx.io"
GRUPO = 21

TEMP_MIN, TEMP_MAX = 0, 50
SOM_MIN, SOM_MAX = 0, 50

grafo_labirinto = {
    1: [2, 3], 2: [4, 5], 3: [2], 4: [5],
    5: [3, 6, 7], 6: [8], 7: [5], 8: [10, 9],
    9: [7], 10: [1]
}

# contagem de marsamis nas salas 1 a 10
ocupacao_salas = {i: {'odd': 0, 'even': 0} for i in range(1, 11)}

gatilhos_acionados = {i: 0 for i in range(1, 11)}


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

    # Temperatura
    if msg.topic == f"pisid_mazetemp_{GRUPO}":
        valor = data.get("Temperature")
        hora = data.get("Hour", "")
        sala = data.get("Sala")

        if valor is None:
            return

        # Se a temperatura subir muito, ligar o AC (SetAirConditioner)
        if valor > TEMP_MAX:
            enviar_atuacao(client, "SetAirConditioner", sala)

        # Hora inválida
        if not validar_hora(hora):
            print(f"Hora inválida: {hora} — ignorado")
            db.outliers.insert_one({**data, "tipo": "Temperature", "motivo": "hora_invalida"})
            return

        # Valor fora dos limites
        if float(valor) < TEMP_MIN or float(valor) > TEMP_MAX:
            print(f"temperatura fora dos limites: {valor} — ignorado")
            db.outliers.insert_one({**data, "tipo": "Temperature", "motivo": "valor_fora_limites"})
            return

        db.sensores.insert_one({**data, "tipo": "Temperature"})
        print("Temperatura guardada")

    # som
    elif msg.topic == f"pisid_mazesound_{GRUPO}":
        valor = data.get("Sound")
        hora = data.get("Hour", "")
        sala = data.get("Sala")

        if valor is None:
            return
        #se o ruido subir muito fechar corredor para marsamis pararem um pouco e ele abaixar
        if valor > SOM_MAX:
            enviar_atuacao(client, "CloseDoor", sala)
        else:
            # Se o som baixar, volta a abrir
            enviar_atuacao(client, "OpenDoor", sala)

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

    # movimentos
    elif msg.topic == f"pisid_mazemov_{GRUPO}":
        marsami = data.get("Marsami")
        origem = data.get("RoomOrigin")
        destino = data.get("RoomDestiny")
        status = data.get("Status")

        if marsami is None:
            return

        # vê se o marsami tem id par ou impar
        tipo = 'even' if marsami % 2 == 0 else 'odd'

        # Status 2 = cansado (imobilizado)
        if status == 2:
            print(f"cansado: Marsami {marsami} imobilizado")

        if origem != 0 and origem in grafo_labirinto:
            if destino not in grafo_labirinto[origem]:
                print(f" Movimento impossível: {origem} -> {destino}")
                return

        if origem == 0 and destino != 0:
            ocupacao_salas[destino][tipo] += 1
            print(f"Entrada no labirinto")
            return

        # Se saiu de uma sala real, retira 1
        if origem > 0 and origem in ocupacao_salas:
            if ocupacao_salas[origem][tipo] > 0:
                ocupacao_salas[origem][tipo] -= 1

        # Se entrou numa sala real, soma 1
        if destino > 0 and destino in ocupacao_salas:
            ocupacao_salas[destino][tipo] += 1

            # --- LÓGICA DO GATILHO (Verifica sempre que alguém entra) ---
            n_odd = ocupacao_salas[destino]['odd']
            n_even = ocupacao_salas[destino]['even']

            if n_odd == n_even and n_odd > 0:
                #Faz com que s´hajam 3 por sala
                if gatilhos_acionados[destino] < 3:
                    send_score(client, destino)
                    gatilhos_acionados[destino] += 1
            # -------------------------

        db.sensores.insert_one({**data, "tipo": "Movement"})
        print("Movimento guardado")


def send_score(client, sala):
    payload = {
        "Type": "Score",
        "Player": GRUPO,
        "Room": sala
    }
    topic = "pisid_mazeact"
    client.publish(topic, json.dumps(payload))
    print(f"!!! GATILHO ENVIADO para Sala {sala} (Odd:Even iguais) !!!")

def enviar_atuacao(client, tipo_comando, sala):
    # tipo_comando pode ser: "OpenDoor", "CloseDoor", "SetAirConditioner"
    payload = {
        "Type": tipo_comando,
        "Player": GRUPO,
        "Room": sala
    }
    client.publish("pisid_mazeact", json.dumps(payload))
    print(f"⚠️ ATUAÇÃO ENVIADA: {tipo_comando} na Sala {sala}")

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
        print("[AVISO] Desligado, a reconectar...")


client = mqtt.Client(mqtt.CallbackAPIVersion.VERSION1)
client.on_connect = on_connect
client.on_message = on_message
client.on_disconnect = on_disconnect

client.connect(BROKER, 1883, keepalive=60)
client.loop_forever()
