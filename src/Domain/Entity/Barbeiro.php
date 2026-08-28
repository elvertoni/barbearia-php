<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Entidade Barbeiro — recurso agendável.
 *
 * `usuarioId` liga o recurso a um `Usuario` que autentica (perfil barbeiro).
 * É opcional: um barbeiro pode existir como recurso sem ter login próprio.
 */
final class Barbeiro
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $nome,
        public readonly ?int $usuarioId = null,
        public readonly bool $ativo = true,
    ) {
    }

    public static function criar(string $nome): self
    {
        return new self(id: null, nome: $nome, usuarioId: null, ativo: true);
    }

    public function comId(int $id): self
    {
        return new self(id: $id, nome: $this->nome, usuarioId: $this->usuarioId, ativo: $this->ativo);
    }

    public function comUsuario(int $usuarioId): self
    {
        return new self(id: $this->id, nome: $this->nome, usuarioId: $usuarioId, ativo: $this->ativo);
    }
}
