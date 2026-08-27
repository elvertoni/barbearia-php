<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Enum\StatusAgendamento;
use App\Domain\Exception\TransicaoInvalidaException;
use App\Domain\Repository\AgendamentoRepositoryInterface;

/**
 * Caso de uso: Transitar Status de Agendamento.
 *
 * Valida e executa transições de estado conforme a máquina de estados (Regra 6).
 */
final class TransitarStatusUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $agendamentoRepo,
    ) {
    }

    /**
     * @throws TransicaoInvalidaException
     */
    public function executar(int $agendamentoId, StatusAgendamento $novoStatus): void
    {
        $agendamento = $this->agendamentoRepo->buscarPorId($agendamentoId);

        if ($agendamento === null) {
            throw new \InvalidArgumentException("Agendamento #{$agendamentoId} não encontrado.");
        }

        // Regra 6: Validar transição
        $agendamento->transitar($novoStatus);

        $this->agendamentoRepo->atualizar($agendamento);
    }
}
