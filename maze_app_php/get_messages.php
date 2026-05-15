<?php
require 'config.php';
header('Content-Type: application/json');

$response = ["success" => false, "data" => []];

$sql = "SELECT
            id_alerta AS id,
            hora_alerta AS hora,
            mensagem AS msg,
            valor_registado AS leitura,
            tipo_sensor AS sensor
        FROM alertas
        ORDER BY hora_alerta DESC
        LIMIT 50";

$result = $conn->query($sql);

if ($result) {

    $rows = [];

    while($r = $result->fetch_assoc()) {

        $rows[] = [
            "id" => $r['id'],
            "hora" => $r['hora'],
            "msg" => $r['msg'],
            "leitura" => $r['leitura'],
            "sensor" => $r['sensor']
        ];
    }

    $response["data"] = $rows;
    $response["success"] = true;

} else {

    $response["message"] = "Erro na consulta: " . $conn->error;
}

echo json_encode($response);
?>