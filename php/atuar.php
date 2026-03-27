<?php
include 'config.php';
if(isset($_GET['acao'])) {
    $acao = $_GET['acao'];
    $sala = $_GET['sala'];
    $conn->query("INSERT INTO comandos_pendentes (comando, sala) VALUES ('$acao', '$sala')");
}
header("Location: index.php");
?>