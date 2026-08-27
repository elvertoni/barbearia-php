<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\FilaEspera;
use App\Domain\Repository\FilaEsperaRepositoryInterface;

/**
 * Caso de uso: Entrar na Fila de Espera (Regra 7 do PRD).
 */
final class EntrarFilaEsperaUseCase
{
    public function __construct(
        private readonly FilaEsperaRepositoryInterface $filaEsperaRepo,
    ) {
    }

    public function executar(int $clienteId, int $servicoId, string $dataDesejada): FilaEspera
    {
        $item = FilaEspera::criar(
            clienteId: $clienteId,
            servicoId: $servicoId,
            dataDesejada: $dataDesejada,
        );

        return $this->filaEsperaRepo->salvar($item);
    }
}
