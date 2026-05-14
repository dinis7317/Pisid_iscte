<?php
// perfil.php
session_start();
require 'config.php';

// 1. Validar sessão (Se não houver user_id, expulsa para o login)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$mensagem = "";

// 2. Processar a atualização de dados quando o formulário é submetido (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $novo_username = trim($_POST['username']);
    $novo_email = trim($_POST['email']);
    $nova_password = $_POST['password']; // Opcional
    $nova_equipe = $_POST['id_equipe'];

    // Se o utilizador escreveu uma nova password, atualizamos a password também
    if (!empty($nova_password)) {
        $password_hash = hash('sha256', $nova_password);
        $stmt_update = $conn->prepare("UPDATE utilizadores SET username=?, email=?, password_hash=?, id_equipe=? WHERE id_user=?");
        $stmt_update->bind_param("sssii", $novo_username, $novo_email, $password_hash, $nova_equipe, $id_user);
    } else {
        // Se a password ficou em branco, atualizamos apenas os outros campos
        $stmt_update = $conn->prepare("UPDATE utilizadores SET username=?, email=?, id_equipe=? WHERE id_user=?");
        $stmt_update->bind_param("ssii", $novo_username, $novo_email, $nova_equipe, $id_user);
    }

    if ($stmt_update->execute()) {
        $mensagem = "<p style='color:green; font-weight:bold;'>Perfil atualizado com sucesso!</p>";

        // Atualizamos as variáveis de sessão para refletir as mudanças no sistema em tempo real
        $_SESSION['username'] = $novo_username;
        $_SESSION['id_equipe'] = $nova_equipe;
    } else {
        $mensagem = "<p style='color:red;'>Erro ao atualizar perfil: " . $conn->error . "</p>";
    }
}

// 3. Ir buscar os dados atuais para pré-preencher o formulário
$stmt = $conn->prepare("SELECT username, email, id_equipe FROM utilizadores WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Ir buscar as equipas para preencher o Menu Dropdown (tal como no registo.php)
$res_equipes = $conn->query("SELECT * FROM equipes");
?>

<!DOCTYPE html>
<html>
<head><title>O meu Perfil - PISID</title></head>
<body style="text-align:center; font-family:Arial; padding-top:50px;">
    <h2>O meu Perfil</h2>

    <?php echo $mensagem; ?>

    <form method="POST" style="display: inline-block; text-align: left; background: #f9f9f9; padding: 20px; border: 1px solid #ccc; border-radius: 5px;">

        <label>Nome de Utilizador:</label><br>
        <input type="text" name="username" value="<?php echo htmlspecialchars($user_data['username']); ?>" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required><br><br>

        <label>Nova Palavra-passe:</label><br>
        <input type="password" name="password" placeholder="Deixar em branco para manter atual"><br><br>

        <label>A sua Equipa:</label><br>
        <select name="id_equipe" required>
            <?php while($row = $res_equipes->fetch_assoc()): ?>
                <option value="<?php echo $row['id_equipe']; ?>" <?php if($row['id_equipe'] == $user_data['id_equipe']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($row['nome_equipe']); ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit" style="width: 100%; padding: 10px; cursor: pointer;">Atualizar Dados</button>
    </form>

    <br><br>
    <a href="dashboard.php" style="text-decoration: none; color: #333; border: 1px solid #333; padding: 5px 10px; border-radius: 3px;">Voltar à Dashboard</a>
</body>
</html>