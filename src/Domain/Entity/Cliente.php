<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Entidade Cliente.
 */
final class Cliente
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $nome,
        public readonly string $telefone,
    ) {
    }

    public static function criar(string $nome, string $telefone): self
    {
        return new self(id: null, nome: $nome, telefone: $telefone);
    }

    public function comId(int $id): self
    {
        return new self(id: $id, nome: $this->nome, telefone: $this->telefone);
    }
}
