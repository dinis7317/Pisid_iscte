<?php
require 'config.php';

// Captura os dados tanto de POST (Formulário/App) como de REQUEST
$email = $_REQUEST['email'] ?? ($_REQUEST['username'] ?? ''); 
$password = $_REQUEST['password'] ?? '';
$isAndroid = isset($_REQUEST['database']); // Se vier 'database', sabemos que é a App

$erro = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" || $isAndroid) {
    
    $stmt = $conn->prepare("SELECT id_user, username, password_hash, perfil, id_equipe FROM utilizadores WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Verifica a password (estás a usar texto limpo conforme o teu código anterior)
        if (hash('sha256', $password) === $user['password_hash']) {
            
            if ($isAndroid) {
                // RESPOSTA PARA O ANDROID
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => true,
                    "IDGrupo" => $user['id_equipe'],
                    "message" => "Login com sucesso!"
                ]);
                exit;
            } else {
                // RESPOSTA PARA O BROWSER
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['perfil'] = $user['perfil'];
                $_SESSION['id_equipe'] = $user['id_equipe'];
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $erro = "Senha errada!";
        }
    } else {
        $erro = "Utilizador não encontrado!";
    }

    // Se falhar e for Android, envia JSON de erro
    if ($isAndroid) {
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "message" => $erro]);
        exit;
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
    </form>
    <br>
    <button onclick="window.location.href='registo.php'">Criar Nova Conta</button>
    <?php if($erro) echo "<p style='color:red;'>$erro</p>"; ?>
</body>
</html>
