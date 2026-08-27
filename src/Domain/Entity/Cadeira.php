<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Entidade Cadeira.
 */
final class Cadeira
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $nome,
        public readonly bool $ativo = true,
    ) {
    }

    public static function criar(string $nome): self
    {
        return new self(id: null, nome: $nome, ativo: true);
    }

    public function comId(int $id): self
    {
        return new self(id: $id, nome: $this->nome, ativo: $this->ativo);
    }
}
