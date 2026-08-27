<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Agendamento;
use App\Domain\Enum\StatusAgendamento;
use App\Domain\Exception\TransicaoInvalidaException;
use App\Domain\Repository\AgendamentoRepositoryInterface;
use App\Domain\Repository\FilaEsperaRepositoryInterface;
use PDO;

/**
 * Caso de uso: Cancelar Agendamento.
 *
 * 1. Transita o status para "cancelado" (Regra 6)
 * 2. Verifica a fila de espera para o mesmo dia/serviço (Regra 7)
 * 3. Retorna itens da fila que podem ser atendidos
 */
final class CancelarAgendamentoUseCase
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly AgendamentoRepositoryInterface $agendamentoRepo,
        private readonly FilaEsperaRepositoryInterface $filaEsperaRepo,
    ) {
    }

    /**
     * @return array{agendamento: Agendamento, fila_sinalizada: array} Agendamento cancelado + itens da fila sinalizados
     * @throws TransicaoInvalidaException
     */
    public function executar(int $agendamentoId): array
    {
        $iniciouTransacao = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            $agendamento = $this->agendamentoRepo->buscarPorId($agendamentoId);

            if ($agendamento === null) {
                throw new \InvalidArgumentException("Agendamento #{$agendamentoId} não encontrado.");
            }

            // Regra 6: Validar transição de estado
            $agendamento->transitar(StatusAgendamento::Cancelado);

            // Persistir a mudança de status
            $this->agendamentoRepo->atualizar($agendamento);

            // Regra 7: Verificar fila de espera
            $data = $agendamento->horaInicio->format('Y-m-d');
            $itensFila = $this->filaEsperaRepo->listarPorDataEServico(
                $data,
                $agendamento->servicoId,
            );

            if ($iniciouTransacao && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            return [
                'agendamento' => $agendamento,
                'fila_sinalizada' => $itensFila,
            ];
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
