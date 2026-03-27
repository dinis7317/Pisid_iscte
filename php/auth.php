<?php
session_start();

// Se não houver sessão válida, redireciona para login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Variáveis disponíveis nas páginas protegidas
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_type = $_SESSION['user_type']; // 'admin' ou 'investigador'
?>
