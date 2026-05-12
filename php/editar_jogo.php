<?php
session_start();
require 'config.php';
require 'phpMQTT.php';

// -------------------- MQTT CONFIG --------------------
$server = "broker.hivemq.com";
$port = 1883;
$client_id = "php_edit_jogo_" . uniqid();

// -------------------- INPUT --------------------
$id_jogo = $_GET['id'];
$user_id_atual = $_SESSION['user_id'];

// -------------------- BUSCAR JOGO --------------------
$stmt = $pdo->prepare("
    SELECT id_criador,
           limite_temp,
           limite_som,
           temp_alarmante,
           som_alarmante
    FROM jogo
    WHERE id_jogo = ?
");
$stmt->execute([$id_jogo]);
$jogo = $stmt->fetch();

if (!$jogo) {
    die("Jogo não encontrado.");
}

// -------------------- SEGURANÇA --------------------
if ($jogo['id_criador'] != $user_id_atual) {
    die("⚠️ Apenas o criador pode alterar parâmetros.");
}

// -------------------- UPDATE + MQTT --------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $limite_temp = $_POST['limite_temp'];
    $limite_som = $_POST['limite_som'];
    $temp_alarmante = $_POST['temp_alarmante'];
    $som_alarmante = $_POST['som_alarmante'];

    // UPDATE SQL
    $stmt = $pdo->prepare("
        UPDATE jogo
        SET limite_temp = ?,
            limite_som = ?,
            temp_alarmante = ?,
            som_alarmante = ?
        WHERE id_jogo = ?
    ");

    $stmt->execute([
        $limite_temp,
        $limite_som,
        $temp_alarmante,
        $som_alarmante,
        $id_jogo
    ]);

    // ---------------- MQTT ----------------
    $mqtt = new phpMQTT($server, $port, $client_id);

    if ($mqtt->connect()) {

        $payload = json_encode([
            "temp_alarmante" => (float)$temp_alarmante,
            "som_alarmante" => (float)$som_alarmante,
            "limite_temp" => (float)$limite_temp,
            "limite_som" => (float)$limite_som
        ]);

        $topic = "pisid_config_" . $id_jogo;

        $mqtt->publish($topic, $payload, 0);
        $mqtt->close();
    }

    echo "<p style='color:green;'>✔ Parâmetros atualizados e enviados ao sistema!</p>";
}
?>

<!-- -------------------- FORM -------------------- -->

<h2>🎮 Editar Jogo #<?= $id_jogo ?></h2>

<form method="POST">

    <label>Limite de Temperatura:</label><br>
    <input type="number" name="limite_temp"
           value="<?= $jogo['limite_temp'] ?>"><br><br>

    <label>Limite de Som:</label><br>
    <input type="number" name="limite_som"
           value="<?= $jogo['limite_som'] ?>"><br><br>

    <label>Temperatura Alarmante:</label><br>
    <input type="number" name="temp_alarmante"
           value="<?= $jogo['temp_alarmante'] ?>"><br><br>

    <label>Som Alarmante:</label><br>
    <input type="number" name="som_alarmante"
           value="<?= $jogo['som_alarmante'] ?>"><br><br>

    <button type="submit">Guardar alterações</button>

</form>