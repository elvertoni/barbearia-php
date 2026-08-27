<?php

declare(strict_types=1);

namespace App\Domain\Entity;

/**
 * Entidade Serviço.
 * Preço em centavos (inteiro) — nunca float, conforme PRD.
 */
final class Servico
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $nome,
        public readonly int $duracaoMinutos,
        public readonly int $precoCentavos,
        public readonly bool $ativo = true,
    ) {
    }

    public static function criar(string $nome, int $duracaoMinutos, int $precoCentavos): self
    {
        return new self(
            id: null,
            nome: $nome,
            duracaoMinutos: $duracaoMinutos,
            precoCentavos: $precoCentavos,
            ativo: true,
        );
    }

    public function comId(int $id): self
    {
        return new self(
            id: $id,
            nome: $this->nome,
            duracaoMinutos: $this->duracaoMinutos,
            precoCentavos: $this->precoCentavos,
            ativo: $this->ativo,
        );
    }

    /**
     * Retorna o preço formatado em reais (apenas para apresentação).
     */
    public function precoFormatado(): string
    {
        return 'R$ ' . number_format($this->precoCentavos / 100, 2, ',', '.');
    }
}
