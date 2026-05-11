<?php
session_start();
require 'config.php';

// 1. Bloqueio de Segurança: Só Admins entram
if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$mensagem = "";

// --- LÓGICA DE PROCESSAMENTO (POST) ---

// A. Criar Equipa
if (isset($_POST['btn_criar_equipa'])) {
    $nome = $_POST['nome_equipe'];
    $stmt = $pdo->prepare("INSERT INTO equipes (nome_equipe) VALUES (?)");
    $stmt->execute([$nome]);
    $mensagem = "Equipa '$nome' criada com sucesso!";
}

// B. Criar Utilizador
if (isset($_POST['btn_criar_user'])) {
    $user = $_POST['username'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $eq = $_POST['id_equipe'] ?: null;
    $perfil = $_POST['perfil'];

    $stmt = $pdo->prepare("INSERT INTO utilizadores (username, password_hash, id_equipe, perfil) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user, $pass, $eq, $perfil]);
    $mensagem = "Utilizador '$user' registado!";
}

// C. Apagar Utilizador
if (isset($_GET['del_user'])) {
    $stmt = $pdo->prepare("DELETE FROM utilizadores WHERE id_user = ?");
    $stmt->execute([$_GET['del_user']]);
    $mensagem = "Utilizador removido.";
}

// D. Reiniciar Migração (O "Botão de Pânico")
if (isset($_POST['btn_migracao'])) {
    // Aqui podes chamar o teu script Python ou uma SP de limpeza
    // shell_exec("python3 migracao.py"); 
    $mensagem = "⚠️ Comando de reinicialização da migração enviado!";
}

// --- BUSCA DE DADOS PARA AS TABELAS ---
$equipes = $pdo->query("SELECT * FROM equipes ORDER BY nome_equipe ASC")->fetchAll();
$users = $pdo->query("SELECT u.*, e.nome_equipe FROM utilizadores u LEFT JOIN equipes e ON u.id_equipe = e.id_equipe")->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <title>Painel de Administração</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background: #f4f4f9; }
        .card { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #eee; }
        .btn-del { color: red; text-decoration: none; font-weight: bold; }
        .alert { padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>

    <h1>⚙️ Gestão Total do Sistema</h1>
    
    <?php if($mensagem): ?>
        <div class="alert"><?= $mensagem ?></div>
    <?php endif; ?>

    <div class="card" style="border-left: 5px solid #dc3545;">
        <h3>🚀 Manutenção do Sistema</h3>
        <form method="POST">
            <p>Se a migração MongoDB -> MySQL falhar, clique abaixo:</p>
            <button type="submit" name="btn_migracao" style="background: #dc3545; color: white; border: none; padding: 10px 20px; cursor: pointer;">
                Reinicializar Processo de Migração
            </button>
        </form>
    </div>

    <div style="display: flex; gap: 20px;">
        <div class="card" style="flex: 1;">
            <h3>Escalões / Equipas</h3>
            <form method="POST" style="margin-bottom: 15px;">
                <input type="text" name="nome_equipe" placeholder="Nome da Equipa" required>
                <button type="submit" name="btn_criar_equipa">Adicionar</button>
            </form>
            <table>
                <tr><th>Nome</th></tr>
                <?php foreach($equipes as $e): ?>
                    <tr><td><?= $e['nome_equipe'] ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="card" style="flex: 2;">
            <h3>Utilizadores do Sistema</h3>
            <form method="POST" style="margin-bottom: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="id_equipe">
                    <option value="">-- Selecionar Equipa --</option>
                    <?php foreach($equipes as $e): ?>
                        <option value="<?= $e['id_equipe'] ?>"><?= $e['nome_equipe'] ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="perfil">
                    <option value="user">User Comum</option>
                    <option value="admin">Administrador</option>
                </select>
                <button type="submit" name="btn_criar_user" style="grid-column: span 2;">Criar Utilizador</button>
            </form>

            <table>
                <tr>
                    <th>User</th>
                    <th>Perfil</th>
                    <th>Equipa</th>
                    <th>Ação</th>
                </tr>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><?= $u['username'] ?></td>
                    <td><strong><?= strtoupper($u['perfil']) ?></strong></td>
                    <td><?= $u['nome_equipe'] ?? '—' ?></td>
                    <td><a href="?del_user=<?= $u['id_user'] ?>" class="btn-del" onclick="return confirm('Apagar?')">Apagar</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <p><a href="dashboard.php">← Voltar ao Dashboard</a></p>

</body>
</html>