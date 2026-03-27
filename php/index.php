<?php include 'auth.php'; ?>
<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>PISID - Grupo 21 - Labirinto</title>
    <meta http-equiv="refresh" content="2">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; text-align: center; }
        table { margin: 20px auto; border-collapse: collapse; width: 80%; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        th, td { padding: 12px; border: 1px solid #ddd; }
        th { background-color: #2c3e50; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .alert { color: red; font-weight: bold; }
    </style>
</head>
<body>

    <p>Olá, <strong><?= htmlspecialchars($user_name) ?></strong> (<?= $user_type ?>) | <a href="logout.php">Sair</a></p>

    <h1>Monitorização do Labirinto - Grupo 21</h1>

    <h2>Ocupação Atual das Salas</h2>
    <table>
        <thead>
            <tr>
                <th>Sala</th>
                <th>Marsamis Ímpares (Odd)</th>
                <th>Marsamis Pares (Even)</th>
                <th>Total na Sala</th>
                <?php if ($user_type === 'admin'): ?>
                    <th>Ações</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM ocupacao_salas ORDER BY id_sala ASC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>Sala " . $row["id_sala"] . "</td>
                            <td>" . $row["n_odd"] . "</td>
                            <td>" . $row["n_even"] . "</td>
                            <td>" . $row["n_total"] . "</td>";

                    if ($user_type === 'admin') {
                        echo "<td>
                                <a href='atuar.php?acao=CloseDoor&sala=".$row["id_sala"]."' style='color:red;'>Fechar Porta</a> |
                                <a href='atuar.php?acao=SetAirConditioner&sala=".$row["id_sala"]."' style='color:blue;'>Ligar AC</a> |
                                <a href='atuar.php?acao=OpenDoor&sala=".$row["id_sala"]."' style='color:green;'>Abrir</a>
                              </td>";
                    }
                    echo "</tr>";
                }
            }
            ?>
        </tbody>
    </table>

    <hr>

    <h2>Últimas Leituras de Sensores</h2>
    <div style="display: flex; justify-content: space-around;">
        <div>
            <h3>Temperatura</h3>
            <?php
            $resTemp = $conn->query("SELECT * FROM Temperatura ORDER BY Hora DESC LIMIT 5");
            while($t = $resTemp->fetch_assoc()) echo "<p>" . $t['Hora'] . ": " . $t['Temperatura'] . "ºC</p>";
            ?>
        </div>
        <div>
            <h3>Som</h3>
            <?php
            $resSom = $conn->query("SELECT * FROM Som ORDER BY Hora DESC LIMIT 5");
            while($s = $resSom->fetch_assoc()) echo "<p>" . $s['Hora'] . ": " . $s['Som'] . " dB</p>";
            ?>
        </div>
    </div>

</body>
</html>