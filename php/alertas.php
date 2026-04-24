<?php
require 'config.php';

// Ir buscar os alertas mais recentes
$stmt = $pdo->query("SELECT * FROM alertas ORDER BY hora_alerta DESC LIMIT 10");
$alertas = $stmt->fetchAll();
?>

<h2>Alertas do Sistema</h2>
<table border="1">
    <tr>
        <th>Hora</th>
        <th>Tipo</th>
        <th>Valor</th>
        <th>Mensagem</th>
    </tr>
    <?php foreach ($alertas as $row): ?>
    <tr>
        <td><?= $row['hora_alerta'] ?></td>
        <td><?= $row['tipo_sensor'] ?></td>
        <td><?= $row['valor_registado'] ?></td>
        <td><strong><?= $row['mensagem'] ?></strong></td>
    </tr>
    <?php endforeach; ?>
</table>