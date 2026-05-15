echo 🎮 A iniciar o MazeRun...
cd /d "C:\xampp\htdocs\scripts\mazerun"
start "Simulador" mazerun.exe 21 --broker broker.hivemq.com --portbroker 1883
cd /d "C:\Users\maria\PycharmProjects\Pisid_iscte"
start "Bridge" cmd /k python bridge_mqtt_mongo.py
cd /d "C:\Users\maria\PycharmProjects\Pisid_iscte"
start "Migracao" cmd /k python migracao.py