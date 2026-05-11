<?php
require 'config.php';

$mensagem = "";

// 1. Procurar equipas para preencher o Menu Dropdown
$res_equipes = $conn->query("SELECT * FROM equipes");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password_hash = $_POST['password_hash'];
    $id_equipe = $_POST['id_equipe'];
    $perfil = 'user'; // Por padrão, novos registos são 'user'

    // 2. Verificar se o email já existe
    $checkEmail = $conn->prepare("SELECT email FROM utilizadores WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $resCheck = $checkEmail->get_result();

    if ($resCheck->num_rows > 0) {
        $mensagem = "<p style='color:red;'>Erro: Este email já está registado!</p>";
    } else {
        // 3. Inserir na Base de Dados
        $stmt = $conn->prepare("CALL sp_RegistarUtilizador(?, ?, ?, ?)");
$stmt->bind_param("sssi", $username, $email, $password_hash, $id_equipe);
        if ($stmt->execute()) {
            header("Location: login.php?registo=sucesso");
            exit;
        } else {
            $mensagem = "<p style='color:red;'>Erro ao registar: " . $conn->error . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Registo - PISID</title></head>
<body style="text-align:center; font-family:Arial; padding-top:50px;">
    <h2>Criar Nova Conta</h2>
    <?php echo $mensagem; ?>

    <form method="POST" style="display: inline-block; text-align: left;">
        <label>Nome de Utilizador:</label><br>
        <input type="text" name="username" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Palavra-passe:</label><br>
        <input type="password" name="password_hash" required><br><br>

        <label>Sua Equipa:</label><br>
        <select name="id_equipe" required>
            <option value="">-- Selecione uma Equipa --</option>
            <?php while($row = $res_equipes->fetch_assoc()): ?>
                <option value="<?php echo $row['id_equipe']; ?>">
                    <?php echo $row['nome_equipe']; ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit" style="width: 100%; padding: 10px;">Registar</button>
    </form>

    <p><a href="login.php">Já tenho conta (Login)</a></p>
</body>
</html>