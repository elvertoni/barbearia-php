-- Registro de tentativas de login para rate limiting (Módulo 1).
-- Regra: 5 falhas em 15 minutos para o mesmo e-mail bloqueia novas tentativas.
CREATE TABLE IF NOT EXISTS login_tentativas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    sucesso TINYINT(1) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_lt_email_data (email, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
