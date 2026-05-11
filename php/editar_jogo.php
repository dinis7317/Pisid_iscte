<?php
session_start();
require 'config.php';

$id_jogo = $_GET['id'];
$user_id_atual = $_SESSION['user_id'];

// 1. Verificar quem é o dono do jogo
$stmt = $pdo->prepare("SELECT id_criador, limite_temp FROM jogo WHERE id_jogo = ?");
$stmt->execute([$id_jogo]);
$jogo = $stmt->fetch();

// 2. Bloqueio de Segurança
if ($jogo['id_criador'] != $user_id_atual) {
    die("⚠️ Erro: Apenas o utilizador que iniciou este jogo pode alterar os seus parâmetros.");
}

// 3. Lógica de Update se o formulário for enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $novo_limite = $_POST['limite_temp'];
    $stmt = $pdo->prepare("UPDATE jogo SET limite_temp = ? WHERE id_jogo = ?");
    $stmt->execute([$novo_limite, $id_jogo]);
    echo "Parâmetros atualizados!";
}
?>

<form method="POST">
    <h3>Editar Parâmetros do Jogo #<?= $id_jogo ?></h3>
    <label>Limite de Temperatura:</label>
    <input type="number" name="limite_temp" value="<?= $jogo['limite_temp'] ?>">
    <button type="submit">Guardar</button>
</form>