<?php
session_start();
require 'config.php';
require 'phpMQTT.php';

// -------------------- MQTT CONFIG --------------------
$server = "broker.hivemq.com";
$port = 1883;
$client_id = "php_edit_jogo_" . uniqid();


// Proteção básica: Tem de estar logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$id_equipe = $_SESSION['id_equipe'];
$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $data_atual = date("Y-m-d H:i:s");

    $nome_jogo = $_POST['nome_jogo'];

    $limite_temp = $_POST['limite_temp'];
    $limite_som = $_POST['limite_som'];

    $temp_alarmante = $_POST['temp_alarmante'];
    $som_alarmante = $_POST['som_alarmante'];

    try {

        $sql = "INSERT INTO jogo (
                    nome_jogo,
                    DataInicio,
                    id_criador,
                    id_equipe,
                    limite_temp,
                    limite_som,
                    temp_alarmante,
                    som_alarmante,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ativo')";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $nome_jogo,
            $data_atual,
            $id_user,
            $id_equipe,
            $limite_temp,
            $limite_som,
            $temp_alarmante,
            $som_alarmante
        ]);

        $id_novo_jogo = $pdo->lastInsertId();

      pclose(popen('start "" /B "C:\\xampp\\htdocs\\scripts\\start_game.bat"', "r"));  $mensagem = "<div class='alert'>Jogo #$id_novo_jogo iniciado com sucesso!</div>";
        $mqtt = new phpMQTT($server, $port, $client_id);

if ($mqtt->connect()) {

    $payload = json_encode([
        "temp_alarmante" => (float)$temp_alarmante,
        "som_alarmante" => (float)$som_alarmante,
        "limite_temp" => (float)$limite_temp,
        "limite_som" => (float)$limite_som
    ]);

    $topic = "pisid_config_21";

    $mqtt->publish($topic, $payload, 0);
    $mqtt->close();
}
    } catch (PDOException $e) {
        $mensagem = "<div class='alert' style='background: #f8d7da;'>Erro: " . $e->getMessage() . "</div>";
    }
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