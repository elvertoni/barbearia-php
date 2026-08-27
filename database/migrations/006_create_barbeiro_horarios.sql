-- Horários de trabalho por barbeiro (Regra 3 do PRD).
-- dia_semana: 1=segunda, 2=terça, ..., 7=domingo
CREATE TABLE IF NOT EXISTS barbeiro_horarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    barbeiro_id INT UNSIGNED NOT NULL,
    dia_semana TINYINT UNSIGNED NOT NULL COMMENT '1=seg, 2=ter, 3=qua, 4=qui, 5=sex, 6=sab, 7=dom',
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    CONSTRAINT fk_bh_barbeiro FOREIGN KEY (barbeiro_id) REFERENCES barbeiros(id) ON DELETE CASCADE,
    UNIQUE KEY uk_bh_barbeiro_dia (barbeiro_id, dia_semana),
    CONSTRAINT chk_bh_horario CHECK (hora_fim > hora_inicio),
    CONSTRAINT chk_bh_dia_semana CHECK (dia_semana BETWEEN 1 AND 7)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
