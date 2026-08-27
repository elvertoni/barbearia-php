<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

/**
 * Entidade Fila de Espera (Regra 7 do PRD).
 */
final class FilaEspera
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $clienteId,
        public readonly int $servicoId,
        public readonly string $dataDesejada,
        public readonly DateTimeImmutable $criadoEm,
    ) {
    }

    public static function criar(int $clienteId, int $servicoId, string $dataDesejada): self
    {
        return new self(
            id: null,
            clienteId: $clienteId,
            servicoId: $servicoId,
            dataDesejada: $dataDesejada,
            criadoEm: new DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    public function comId(int $id): self
    {
        return new self(
            id: $id,
            clienteId: $this->clienteId,
            servicoId: $this->servicoId,
            dataDesejada: $this->dataDesejada,
            criadoEm: $this->criadoEm,
        );
    }
}
