<?php
session_start();
require 'config.php';

$id_equipe_user = $_SESSION['id_equipe']; // Garantir que isto foi guardado no Login
$user_id_atual = $_SESSION['user_id'];

// Buscar jogos da MINHA equipa
$sql = "SELECT j.*, u.username as criador
        FROM jogo j
        JOIN utilizadores u ON j.id_criador = u.id_user
        WHERE j.id_equipe = ?
        ORDER BY j.DataInicio DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_equipe_user]);
$jogos = $stmt->fetchAll();
?>

<h2>Jogos da Minha Equipa</h2>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Criador</th>
        <th>Data</th>
        <th>Ações</th>
    </tr>
    <?php foreach ($jogos as $jogo): ?>
    <tr>
        <td><?= $jogo['id_jogo'] ?></td>
        <td><?= $jogo['nome_jogo']?></td>
        <td><?= $jogo['criador'] ?></td>
        <td><?= $jogo['DataInicio'] ?></td>
        <td>
            <?php if ($jogo['id_criador'] == $user_id_atual): ?>
                <a href="editar_jogo.php?id=<?= $jogo['id_jogo'] ?>">Editar Parâmetros</a>
            <?php else: ?>
                <span style="color:gray">Sem permissão para editar</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>