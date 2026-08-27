-- Tabela associativa: quais cadeiras são compatíveis com quais serviços.
-- Regra 2 do PRD: nem toda cadeira serve para todo serviço (ex.: cadeira de barba exige pia).
CREATE TABLE IF NOT EXISTS cadeira_servico_compativel (
    cadeira_id INT UNSIGNED NOT NULL,
    servico_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (cadeira_id, servico_id),
    CONSTRAINT fk_csc_cadeira FOREIGN KEY (cadeira_id) REFERENCES cadeiras(id) ON DELETE CASCADE,
    CONSTRAINT fk_csc_servico FOREIGN KEY (servico_id) REFERENCES servicos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
