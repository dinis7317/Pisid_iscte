<?php
require 'config.php';
header('Content-Type: application/json');

$response = ["success" => false, "data" => []];

$sql = "SELECT id_sala as Sala, n_even as NumeroMarsamisEven, n_odd as NumeroMarsamisOdd FROM ocupacao_salas";
$result = $conn->query($sql);

if ($result) {
    $rows = [];
    while($r = $result->fetch_assoc()) {
        $rows[] = $r;
    }
    $response["data"] = $rows;
    $response["success"] = true;
}

echo json_encode($response);