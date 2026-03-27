<?php
$host = "127.0.0.1";
$user = "root";
$pass = "dinissilva2004";
$db   = "pisid_maze";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Erro: " . $conn->connect_error); }
$sql = "CREATE TABLE IF NOT EXISTS utilizadores (id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(100) NOT NULL, email VARCHAR(100) UNIQUE NOT NULL, password VARCHAR(255) NOT NULL, tipo_utilizador ENUM('admin', 'investigador') DEFAULT 'investigador'); INSERT IGNORE INTO utilizadores (nome, email, password, tipo_utilizador) VALUES ('Dinis', 'dinis@email.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');";
if ($conn->multi_query($sql)) { echo "Tabela de utilizadores e utilizador teste criados com sucesso!"; } else { echo "Erro: " . $conn->error; }
?>
