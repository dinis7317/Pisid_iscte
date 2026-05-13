import paho.mqtt.client as mqtt
import subprocess

BROKER = "broker.hivemq.com"
TOPICO = "pisid_maze21_control"

def on_message(client, userdata, msg):
    if msg.payload.decode() == "START_GAME":
        print("\n[!] Sinal do Mac recebido! A abrir o Labirinto...")
        # Executa o teu ficheiro .bat
        subprocess.Popen([r"C:\Users\maria\PycharmProjects\Pisid_iscte\scripts\arrancar_labirinto.bat"], shell=True)

client = mqtt.Client()
client.on_message = on_message
client.connect(BROKER, 1883, 60)
client.subscribe(TOPICO)
print("📡 PC 2 Aguardando comando do Mac...")
client.loop_forever()