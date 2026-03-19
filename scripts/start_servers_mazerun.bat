@echo off
cls

:: 1. Iniciar os Mongos (Já sabemos que funcionam!)
set BASE_PATH="C:\Users\maria\OneDrive\Ambiente de Trabalho\3ANO\Pisid\replica_dados_pisid"
start "Mongo_S1" mongod --config %BASE_PATH%\server1\server1.conf
start "Mongo_S2" mongod --config %BASE_PATH%\server2\server2.conf
start "Mongo_S3" mongod --config %BASE_PATH%\server3\server3.conf

echo ⏳ Aguardando arranque...
timeout /t 10 /nobreak

:: 2. Tentar configurar (Se já estiver configurado, ele apenas dá erro e segue, não faz mal)
mongosh --port 27019 --eval "rs.initiate(); rs.add('localhost:25019'); rs.add('localhost:23019');"

:: 3. Iniciar o MazeRun (MUITO IMPORTANTE: cd /d e aspas)
echo 🎮 A iniciar o MazeRun...
cd /d "C:\Users\maria\OneDrive\Ambiente de Trabalho\3ANO\Pisid\mazerun"
start "Simulador" mazerun.exe 21 --broker broker.hivemq.com --portbroker 1883

:: 4. Iniciar a Bridge (Assume-se que está na mesma pasta ou na pasta Pisid)
echo 🐍 A iniciar a Bridge...
cd /d "C:\Users\maria\OneDrive\Ambiente de Trabalho\3ANO\Pisid"
start "Bridge" cmd /k "python bridge_mqtt_mongo.py"

:: 5. Iniciar o Monitor
echo 📊 A iniciar o Monitor...
cd /d "C:\Users\maria\OneDrive\Ambiente de Trabalho\3ANO\Pisid\graficos"
start "Monitor" monitmaze.exe 21

echo.
echo ✅ TUDO LANÇADO!
pause