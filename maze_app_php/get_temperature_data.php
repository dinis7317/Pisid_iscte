<?php
require 'config.php';
header('Content-Type: application/json');

$response = ["success" => false, "data" => []];

// Definimos os nomes (aliases) aqui: id vira 'idtemperatura' e Temperatura vira 'temperatura'
$sql = "SELECT id as idtemperatura, Temperatura as temperatura FROM temperatura ORDER BY id DESC LIMIT 20";
$result = $conn->query($sql);

if ($result) {
    $rows = [];
    while($r = $result->fetch_assoc()) {
        // IMPORTANTE: Se no SELECT usaste "as temperatura",
        // aqui tens de usar "temperatura" (e não "value" ou "Temperatura")
        $rows[] = [
            "id" => (int)$r['idtemperatura'],
            "value" => (float)$r['temperatura']
        ];
    }
    $response["data"] = array_reverse($rows);
    $response["success"] = true;
} else {
    $response["error"] = $conn->error;
}

echo json_encode($response);