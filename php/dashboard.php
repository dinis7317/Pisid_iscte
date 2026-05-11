<?php
require 'config.php';

// Se o utilizador não estiver logado, volta para o login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Dashboard - PISID</title></head>
<body style="font-family:Arial; margin:20px;">
    <h1>Olá, <?php echo $_SESSION['username']; ?>! 👋</h1>
    <p>Perfil: <strong><?php echo strtoupper($_SESSION['perfil']); ?></strong></p>

    <hr>

    <h3>Menu Principal</h3>
    <ul>
        <li><a href="perfil.php">👤 Meus Dados Pessoais</a></li>
        <li><a href="jogos.php">📋 Ver Jogos da Equipa</a></li>
        <li><a href="novo_jogo.php">🚀 Iniciar Novo Jogo</a></li>

        <?php if ($_SESSION['perfil'] == 'admin'): ?>
            <li style="margin-top:20px;">
                <a href="admin_panel.php" style="color:red; font-weight:bold;">⚙️ Painel de Administração</a>
            </li>
        <?php endif; ?>
    </ul>

    <br>
    <a href="logout.php">Terminar Sessão (Sair)</a>
</body>
</html>