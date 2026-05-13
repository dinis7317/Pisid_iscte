@echo off
:: 1. MazeRun (Envia dados para o Mac)
echo 🎮 A iniciar o MazeRun...
cd /d "C:\xampp\htdocs\scripts\mazerun"
start "Simulador" mazerun.exe 21 --broker broker.hivemq.com --portbroker 1883

:: 2. Bridge (Lê MQTT e guarda no Mongo Local)
cd /d "C:\Users\maria\PycharmProjects\Pisid_iscte"
start "Bridge" cmd /k python bridge_mqtt_mongo.py

:: 3. Migração (Lê do Mongo e escreve no MySQL do Mac)
start "Migracao" cmd /k python migracao.py

echo 📊 A iniciar o Monitor...
cd /d "C:\Users\maria\PycharmProjects\Pisid_iscte\graficos"
start "Monitor" monitmaze.exe 21