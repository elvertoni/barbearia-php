-- Tabela central do sistema. Contém os índices obrigatórios para a query de conflito.
-- Regra 4 do PRD: verificação de sobreposição de intervalos em SQL.
CREATE TABLE IF NOT EXISTS agendamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT UNSIGNED NOT NULL,
    barbeiro_id INT UNSIGNED NOT NULL,
    cadeira_id INT UNSIGNED NOT NULL,
    servico_id INT UNSIGNED NOT NULL,
    hora_inicio DATETIME NOT NULL,
    hora_fim DATETIME NOT NULL,
    status ENUM('solicitado','confirmado','em_atendimento','concluido','cancelado','no_show') NOT NULL DEFAULT 'solicitado',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_ag_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    CONSTRAINT fk_ag_barbeiro FOREIGN KEY (barbeiro_id) REFERENCES barbeiros(id),
    CONSTRAINT fk_ag_cadeira FOREIGN KEY (cadeira_id) REFERENCES cadeiras(id),
    CONSTRAINT fk_ag_servico FOREIGN KEY (servico_id) REFERENCES servicos(id),

    CONSTRAINT chk_ag_horario CHECK (hora_fim > hora_inicio),

    -- Índices obrigatórios para performance da query de conflito (mencionados no README)
    INDEX idx_agendamentos_barbeiro (barbeiro_id, hora_inicio, hora_fim),
    INDEX idx_agendamentos_cadeira (cadeira_id, hora_inicio, hora_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
