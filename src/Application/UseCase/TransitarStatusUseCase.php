<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Enum\StatusAgendamento;
use App\Domain\Exception\AcessoNegadoException;
use App\Domain\Exception\TransicaoInvalidaException;
use App\Domain\Repository\AgendamentoRepositoryInterface;

/**
 * Caso de uso: Transitar Status de Agendamento.
 *
 * Valida e executa transições de estado conforme a máquina de estados (Regra 6).
 * Se $restritoBarbeiroId for informado, o agendamento precisa pertencer a esse
 * barbeiro — usado para isolar a operação do perfil barbeiro à própria agenda.
 */
final class TransitarStatusUseCase
{
    public function __construct(
        private readonly AgendamentoRepositoryInterface $agendamentoRepo,
    ) {
    }

    /**
     * @throws TransicaoInvalidaException
     * @throws AcessoNegadoException
     */
    public function executar(
        int $agendamentoId,
        StatusAgendamento $novoStatus,
        ?int $restritoBarbeiroId = null,
    ): void {
        $agendamento = $this->agendamentoRepo->buscarPorId($agendamentoId);

        if ($agendamento === null) {
            throw new \InvalidArgumentException("Agendamento #{$agendamentoId} não encontrado.");
        }

        if ($restritoBarbeiroId !== null && $agendamento->barbeiroId !== $restritoBarbeiroId) {
            throw new AcessoNegadoException('este agendamento não pertence a você.');
        }

        // Regra 6: Validar transição
        $agendamento->transitar($novoStatus);

        $this->agendamentoRepo->atualizar($agendamento);
    }
}
