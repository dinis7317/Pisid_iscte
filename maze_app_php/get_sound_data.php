<?php
require 'config.php';
header('Content-Type: application/json');

$response = ["success" => false, "data" => []];

// Ajustado para 'idsom' e 'som' conforme o teu SoundData.java
$sql = "SELECT id as idsom, Som as som FROM som ORDER BY id DESC LIMIT 20";
$result = $conn->query($sql);

if ($result) {
    $rows = [];
    while($r = $result->fetch_assoc()) {
        $rows[] = [
            "idsom" => (int)$r['idsom'],
            "som" => (float)$r['som']
        ];
    }
    $response["data"] = array_reverse($rows);
    $response["success"] = true;
}
echo json_encode($response);