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


# --- Adiciona estas variáveis de estado no TOPO do código (fora das funções) ---
estado_ac = None
estado_portas = None


def on_message(client, userdata, msg):
    global estado_ac, estado_portas  # Para podermos alterar as variáveis acima

    raw = msg.payload.decode()

    try:
        data = json.loads(raw)
    except:
        return
    data["_recebido_em"] = datetime.now()
    tipo_registo = ""
    validado = True

    # --- LÓGICA DE TEMPERATURA ---
    if msg.topic == f"pisid_mazetemp_{GRUPO}":
        valor = data.get("Temperature")
        if valor is not None:
            novo_estado_ac = "AcOn" if valor > temperatura_alarmante else "AcOff"

            # SÓ ENVIA SE O ESTADO MUDAR
            if novo_estado_ac != estado_ac:
                enviar_atuacao(client, novo_estado_ac)
                estado_ac = novo_estado_ac

    # --- LÓGICA DE SOM ---
    elif msg.topic == f"pisid_mazesound_{GRUPO}":
        valor = data.get("Sound")
        if valor is not None:

            novo_estado_portas = "CloseAllDoor" if valor > som_alarmante else "OpenAllDoor"

            # SÓ ENVIA SE O ESTADO MUDAR
            if novo_estado_portas != estado_portas:
                enviar_atuacao(client, novo_estado_portas)
                estado_portas = novo_estado_portas

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
                    validado = False

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


    if validado and tipo_registo != "":
        data["tipo"] = tipo_registo
        # Guarda no MongoDB
        res = db.sensores.insert_one(data)

        # Prepara para migração
        data["_id"] = str(res.inserted_id)
        topic_migracao = f"pisid_migration_{GRUPO}"
        client.publish(topic_migracao, json.dumps(data, default=str))

        print(f"[{tipo_registo}] Migrado para SQL via tópico. ID: {data['_id']}")


def send_score(client, sala):

    payload = {
        "Type": "Score",
        "Player": int(GRUPO),
        "Sala": int(sala)
    }

    mensagem = json.dumps(payload, separators=(',', ':'))
    client.publish("pisid_mazeact", mensagem)
    print(f"🎯 [SENT] Score enviado: {mensagem}")

def enviar_atuacao(client, comando):

    payload = {
        "Type": str(comando),
        "Player": int(GRUPO)
    }
    mensagem = json.dumps(payload, separators=(',', ':'))
    client.publish("pisid_mazeact", mensagem)
    print(f"⚠️ [SENT] Atuação: {mensagem}")

def on_connect(client, userdata, flags, rc):
    print("Bridge ligada. A subscrever tópicos...")
    client.subscribe([(f"pisid_mazetemp_{GRUPO}", 0), (f"pisid_mazesound_{GRUPO}", 0), (f"pisid_mazemov_{GRUPO}", 0)])


client = mqtt.Client(mqtt.CallbackAPIVersion.VERSION1)
client.on_connect = on_connect
client.on_message = on_message
client.connect(BROKER, 1883, keepalive=60)
client.loop_forever()