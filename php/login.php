<?php
// login.php
require 'config.php';

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Usamos o $conn que criaste no config.php
    $stmt = $conn->prepare("SELECT id_user, username, password_hash, perfil, id_equipe FROM utilizadores WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if ($password === $user['password_hash']) {
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['perfil'] = $user['perfil'];
            $_SESSION['id_equipe'] = $user['id_equipe'];

            header("Location: dashboard.php");
            exit;
        } else {
            $erro = "Senha errada!";
        }
    } else {
        $erro = "Utilizador não encontrado!";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Login PISID</title></head>
<body style="text-align:center; font-family:Arial; padding-top:50px;">
    <h2>Login - Grupo 21</h2>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="password" name="password" placeholder="Senha" required><br><br>
        <button type="submit">Entrar</button>
       <button onclick="window.location.href='registo.php'">
            Criar Nova Conta
        </button>
    </form>
    <?php if($erro) echo "<p style='color:red;'>$erro</p>"; ?>
</body>
</html>