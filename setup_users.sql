USE pisid_maze;

CREATE TABLE IF NOT EXISTS utilizadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    tipo_utilizador ENUM('admin', 'investigador') DEFAULT 'investigador'
);

-- Utilizador de teste: admin / password
INSERT IGNORE INTO utilizadores (nome, email, password, tipo_utilizador) 
VALUES ('Admin', 'admin@email.com', 'admin', 'admin');
