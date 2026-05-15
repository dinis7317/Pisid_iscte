<?php
require 'config.php';
header('Content-Type: application/json');

echo json_encode([
    "success" => true,
    "data" => [
        "maximo" => 75.0 // Limite de 75dB
    ]
]);