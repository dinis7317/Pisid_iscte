import paho.mqtt.client as mqtt
from pymongo import MongoClient
import json
from datetime import datetime

mongo = MongoClient("mongodb://localhost:27017/")
db = mongo["pisid"]

BROKER = "broker.hivemq.com"
GRUPO = 21

TEMP_MIN, TEMP_MAX = 0, 50
SOM_MIN, SOM_MAX = 0, 50
temperatura_alarmante = 35
som_alarmante = 35

grafo_labirinto = {
    1: [2, 3], 2: [4, 5], 3: [2], 4: [5],
    5: [3, 6, 7], 6: [8], 7: [5], 8: [10, 9],
    9: [7], 10: [1]
}

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
    try:
        data = json.loads(raw)
    except json.JSONDecodeError:
        print(f"JSON inválido: {raw}")
        return

    data["_recebido_em"] = datetime.now()
    tipo_registo = ""  # Usamos uma variável diferente para não confundir com a lógica interna
    validado = True

    # --- TEMPERATURA ---
    if msg.topic == f"pisid_mazetemp_{GRUPO}":
        tipo_registo = "Temperature"
        valor = data.get("Temperature")
        hora = data.get("Hour", "")
        sala = data.get("Sala")

        if valor is None or not validar_hora(hora):
            validado = False

        elif float(valor) < TEMP_MIN or float(valor) > TEMP_MAX:
            print(f"Temperatura fora dos limites: {valor} — ignorado")
            db.outliers.insert_one({**data, "tipo": "Temperature", "motivo": "valor_fora_limites"})
            validado = False

        if validado and valor > temperatura_alarmante:
            enviar_atuacao(client, "SetAirConditioner", sala)

    # --- SOM ---
    elif msg.topic == f"pisid_mazesound_{GRUPO}":
        tipo_registo = "Sound"
        valor = data.get("Sound")
        hora = data.get("Hour", "")
        sala = data.get("Sala")

        if valor is None or not validar_hora(hora):
            validado = False

        elif float(valor) < SOM_MIN or float(valor) > SOM_MAX:
            print(f"Som fora dos limites: {valor} — ignorado")
            db.outliers.insert_one({**data, "tipo": "Sound", "motivo": "valor_fora_limites"})
            validado = False

        if validado:
            comando = "CloseDoor" if valor > som_alarmante else "OpenDoor"
            enviar_atuacao(client, comando, sala)

    # --- MOVIMENTOS ---
    elif msg.topic == f"pisid_mazemov_{GRUPO}":
        tipo_registo = "Movement"
        marsami = data.get("Marsami")
        origem = data.get("RoomOrigin")
        destino = data.get("RoomDestiny")
        status = data.get("Status")

        if marsami is None:
            validado = False
        else:
            subtipo = 'even' if marsami % 2 == 0 else 'odd'

            # Validação de Grafo
            if origem != 0 and origem in grafo_labirinto:
                if destino not in grafo_labirinto[origem]:
                    print(f"Movimento impossível: {origem} -> {destino}")
                    validado = False  # Não migramos movimentos impossíveis

            if validado:
                # Atualização de ocupação local
                if origem == 0 and destino != 0:
                    ocupacao_salas[destino][subtipo] += 1
                else:
                    if origem > 0 and origem in ocupacao_salas:
                        ocupacao_salas[origem][subtipo] = max(0, ocupacao_salas[origem][subtipo] - 1)
                    if destino > 0 and destino in ocupacao_salas:
                        ocupacao_salas[destino][subtipo] += 1

                        # Gatilho de Score
                        n_odd = ocupacao_salas[destino]['odd']
                        n_even = ocupacao_salas[destino]['even']
                        if n_odd == n_even and n_odd > 0 and gatilhos_acionados[destino] < 3:
                            send_score(client, destino)
                            gatilhos_acionados[destino] += 1

    # --- BLOCO FINAL: SALVAMENTO E MIGRAÇÃO ---
    if validado and tipo_registo != "":
        data["tipo"] = tipo_registo
        # Guarda no MongoDB
        res = db.sensores.insert_one(data)

        # Prepara para migração
        data["_id"] = str(res.inserted_id)
        topic_migracao = f"pisid_migration_{GRUPO}"
        client.publish(topic_migracao, json.dumps(data, default=str))

        print(f"[{tipo_registo}] Migrado para SQL via tópico. ID: {data['_id']}")


# Funções auxiliares mantêm-se iguais...
def send_score(client, sala):
    payload = {"Type": "Score", "Player": GRUPO, "Room": sala}
    client.publish("pisid_mazeact", json.dumps(payload))


def enviar_atuacao(client, tipo_comando, sala):
    payload = {"Type": tipo_comando, "Player": GRUPO, "Room": sala}
    client.publish("pisid_mazeact", json.dumps(payload))
    print(f"⚠️ ATUAÇÃO: {tipo_comando} na Sala {sala}")


def on_connect(client, userdata, flags, rc):
    print("Bridge ligada. A subscrever tópicos...")
    client.subscribe([(f"pisid_mazetemp_{GRUPO}", 0), (f"pisid_mazesound_{GRUPO}", 0), (f"pisid_mazemov_{GRUPO}", 0)])


client = mqtt.Client(mqtt.CallbackAPIVersion.VERSION1)
client.on_connect = on_connect
client.on_message = on_message
client.connect(BROKER, 1883, keepalive=60)
client.loop_forever()