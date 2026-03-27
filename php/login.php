<?php
include 'config.php';
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $res = $conn->query("SELECT * FROM utilizadores WHERE email='$email' AND password='$password'");
    if ($res->num_rows > 0) {
        $_SESSION['user'] = $email;
        header("Location: index.php");
    } else { $erro = "Login falhou!"; }
}
?>
<!DOCTYPE html>
<html>
<head><title>Login</title></head>
<body style="text-align:center; font-family:Arial; margin-top:100px;">
    <h2>PISID - Grupo 21</h2>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required><br><br>
        <input type="password" name="password" placeholder="Senha" required><br><br>
        <button type="submit">Entrar</button>
    </form>
    <?php if(isset($erro)) echo "<p style='color:red;'>$erro</p>"; ?>
</body>
</html>