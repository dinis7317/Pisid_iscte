<?php
include 'config.php';
header('Content-Type: application/json');
$res = $conn->query("SELECT * FROM ocupacao_salas");
$dados = [];
while($row = $res->fetch_assoc()) { $dados[] = $row; }
echo json_encode($dados);
?>