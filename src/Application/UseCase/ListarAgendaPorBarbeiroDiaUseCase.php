<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Agendamento;
use App\Domain\Repository\AgendamentoRepositoryInterface;

/**
 * Caso de uso: Listar Agenda por Barbeiro e Dia.
 */
final class ListarAgendaPorBarbeiroDiaUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $agendamentoRepo,
    ) {
    }

    /**
     * @return Agendamento[]
     */
    public function executar(int $barbeiroId, string $data): array
    {
        return $this->agendamentoRepo->listarPorBarbeiroEDia($barbeiroId, $data);
    }
}
