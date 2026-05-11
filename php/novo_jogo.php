<?php
session_start();
require 'config.php';

// Proteção básica: Tem de estar logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['user_id'];
$id_equipe = $_SESSION['id_equipe']; // Assumindo que guardaste isto no login
$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome_jogo = $_POST['nome_jogo'];
    $limite_t = $_POST['limite_temp'];

    try {
        // Criar o jogo na BD guardando quem o criou e a que equipa pertence
        $sql = "INSERT INTO jogo (id_criador, id_equipe, limite_temp, status) VALUES (?, ?, ?, 'ativo')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_user, $id_equipe, $limite_t]);

        $id_novo_jogo = $pdo->lastInsertId();

        // OPCIONAL: Se quiseres que este jogo seja o "atual" para o Python,
        // podes guardar o ID numa tabela de 'sistema' ou apenas deixar o Python ler o último ID.

        $mensagem = "<div class='alert'>Jogo #$id_novo_jogo iniciado com sucesso! Os sensores agora estão a reportar para este jogo.</div>";
    } catch (PDOException $e) {
        $mensagem = "<div class='alert' style='background: #f8d7da;'>Erro ao iniciar jogo: " . $e->getMessage() . "</div>";
    }
}
?>

<h2>🚀 Iniciar Nova Simulação (Jogo)</h2>
<?= $mensagem ?>

<form method="POST">
    <label>Identificação do Jogo (ex: Teste Manhã):</label><br>
    <input type="text" name="nome_jogo" required><br><br>

    <label>Limite de Temperatura para Alerta (°C):</label><br>
    <input type="number" step="0.1" name="limite_temp" value="25.0" required><br><br>

    <button type="submit" style="padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer;">
        Começar Simulação
    </button>
</form>

<p><a href="dashboard.php">Voltar ao Painel Principal</a></p>