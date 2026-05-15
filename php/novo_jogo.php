<?php
session_start();
require 'config.php';
require 'phpMQTT.php';

// Configurações do Broker (IGUAIS PARA TODOS)
$server = "broker.hivemq.com";
$port = 1883;
$client_id = "php_start_21_" . uniqid();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {

        // 2. Inserir novo jogo
        $sql = "INSERT INTO jogo (nome_jogo, DataInicio, id_equipe, status) VALUES (?, NOW(), 21, 'ativo')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_POST['nome_jogo']]);
        $id_novo_jogo = $pdo->lastInsertId();

        // 3. Enviar sinal MQTT para o teu Windows (PC 2)
        $mqtt = new phpMQTT($server, $port, $client_id);
        if ($mqtt->connect()) {
            // Sinal de arranque para o Windows
            $mqtt->publish("pisid_maze21_control", "START_GAME", 0);

            // Configurações para a Bridge/Android
            $config = json_encode([
                "id_jogo" => $id_novo_jogo,
                "temp_limite" => (float)$_POST['limite_temp'],
                "som_limite" => (float)$_POST['limite_som']
            ]);
            $mqtt->publish("pisid_config_21", $config, 0);
            $mqtt->close();
        }
        echo "<div class='alert'>Jogo #$id_novo_jogo iniciado! O PC Windows vai arrancar...</div>";
    } catch (Exception $e) { die("Erro: " . $e->getMessage()); }
}
?>

<h2>🚀 Iniciar Nova Simulação (Jogo)</h2>

<?= $mensagem ?>

<form method="POST">

    <label>Nome do Jogo:</label><br>
    <input type="text" name="nome_jogo" required><br><br>

    <label>Limite de Temperatura:</label><br>
    <input type="number" step="0.1" name="limite_temp" value="25.0" required><br><br>

    <label>Limite de Som:</label><br>
    <input type="number" step="0.1" name="limite_som" value="25.0" required><br><br>

    <label>Temperatura Alarmante:</label><br>
    <input type="number" step="0.1" name="temp_alarmante" value="35.0" required><br><br>

    <label>Som Alarmante:</label><br>
    <input type="number" step="0.1" name="som_alarmante" value="35.0" required><br><br>

    <button type="submit" style="padding: 10px 20px; background: #28a745; color: white; border: none;">
        Começar Simulação
    </button>

</form>