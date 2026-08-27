-- Fila de espera: cliente pode entrar quando não há slot disponível (Regra 7 do PRD).
CREATE TABLE IF NOT EXISTS fila_espera (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    servico_id INT UNSIGNED NOT NULL,
    data_desejada DATE NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_fe_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    CONSTRAINT fk_fe_servico FOREIGN KEY (servico_id) REFERENCES servicos(id),

    INDEX idx_fe_data_servico (data_desejada, servico_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
