<?php
session_start();
include 'config.php';

$erro = '';
$sucesso = '';

// Se já está autenticado, redirecionar para index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Processar formulário de registo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($nome) || empty($email) || empty($password)) {
        $erro = 'Preencha todos os campos.';
    } elseif ($password !== $password_confirm) {
        $erro = 'As passwords não coincidem.';
    } elseif (strlen($password) < 6) {
        $erro = 'A password deve ter pelo menos 6 caracteres.';
    } else {
        // Verificar se o email já existe
        $stmt = $conn->prepare("SELECT id FROM utilizadores WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $erro = 'Já existe uma conta com este email.';
        } else {
            // Criar o utilizador com password hashed
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("INSERT INTO utilizadores (nome, email, password, tipo_utilizador) VALUES (?, ?, ?, 'investigador')");
            $stmt2->bind_param("sss", $nome, $email, $hashed_password);

            if ($stmt2->execute()) {
                $sucesso = 'Conta criada com sucesso! Pode fazer login.';
            } else {
                $erro = 'Erro ao criar conta. Tente novamente.';
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Registar - PISID Grupo 21</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 40px;
            width: 420px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        .register-container h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 24px;
        }
        .register-container .subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #2c3e50;
            font-weight: 600;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.3s;
            outline: none;
        }
        .form-group input:focus {
            border-color: #27ae60;
        }
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #27ae60, #2ecc71);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        .btn-register:hover {
            opacity: 0.9;
        }
        .erro {
            background: #fee;
            color: #c0392b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #f5c6cb;
        }
        .sucesso {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #c3e6cb;
        }
        .link-login {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }
        .link-login a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }
        .link-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>📝 Criar Conta</h1>
        <p class="subtitle">PISID - Grupo 21</p>

        <?php if ($erro): ?>
            <div class="erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        <?php if ($sucesso): ?>
            <div class="sucesso"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" placeholder="O seu nome" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="exemplo@email.com" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Mínimo 6 caracteres" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirmar Password</label>
                <input type="password" id="password_confirm" name="password_confirm" placeholder="Repita a password" required>
            </div>
            <button type="submit" class="btn-register">Criar Conta</button>
        </form>

        <div class="link-login">
            Já tem conta? <a href="login.php">Entrar aqui</a>
        </div>
    </div>
</body>
</html>
