<?php
// config.php - SEM NENHUM REQUIRE OU INCLUDE
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$db   = 'pisid_maze'; // Confirma se é este o nome da tua BD
$user = 'root';
$pass = '';

// Criar a ligação MySQLi (usada no teu login)
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Falha na ligação: " . $conn->connect_error);
}

// Criar a ligação PDO (usada no teu admin_panel)
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro PDO: " . $e->getMessage());
}
?>