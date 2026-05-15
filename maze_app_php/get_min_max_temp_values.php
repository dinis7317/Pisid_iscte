<?php
require 'config.php';
header('Content-Type: application/json');

$response = ["success" => false, "data" => null];

// Aqui deves adaptar para a tua tabela onde defines os limites do jogo
// Se ainda não tens uma tabela de limites, podes devolver valores fixos para teste:
$response["success"] = true;
$response["data"] = [
    "minimo" => 15.0, // Linha verde
    "maximo" => 35.0  // Linha vermelha
];

echo json_encode($response);