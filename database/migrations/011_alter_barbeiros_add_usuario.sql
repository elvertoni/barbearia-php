-- Liga o recurso agendável `barbeiros` a um `usuarios` que autentica (Módulo 1).
-- Nullable: um barbeiro pode existir como recurso sem ter login próprio.
ALTER TABLE barbeiros
    ADD COLUMN usuario_id INT UNSIGNED NULL AFTER nome,
    ADD CONSTRAINT fk_barbeiros_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    ADD UNIQUE KEY uk_barbeiros_usuario (usuario_id);
