<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "pisid_maze";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erro na ligação: " . $conn->connect_error);
}
?>