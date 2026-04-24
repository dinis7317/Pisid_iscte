<?php
$host = "127.0.0.1";
$db   = 'pisid_maze';
$user = "root";
$pass = "";


$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro na ligação: " . $conn->connect_error);
}
?>