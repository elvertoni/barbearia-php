<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Agendamento;
use App\Domain\Enum\DiaSemana;
use App\Domain\Exception\CadeiraIncompativelException;
use App\Domain\Exception\ConflitoDeHorarioException;
use App\Domain\Exception\ForaDaJanelaDeTrabalhoException;
use App\Domain\Exception\SlotIndisponivelException;
use App\Domain\Repository\AgendamentoRepositoryInterface;
use App\Domain\Repository\BarbeiroHorarioRepositoryInterface;
use App\Domain\Repository\CadeiraRepositoryInterface;
use App\Domain\Repository\ServicoRepositoryInterface;
use DateTimeImmutable;
use PDO;

/**
 * Caso de uso: Criar Agendamento.
 *
 * Orquestra as 7 regras de negócio:
 * 1. Verifica janela de trabalho do barbeiro (Regra 3)
 * 2. Busca cadeiras compatíveis com o serviço (Regra 2)
 * 3. Calcula hora_fim a partir da duração do serviço
 * 4. Dentro de uma transação com FOR UPDATE (Regra 5):
 *    a. Verifica conflito de barbeiro (Regras 1+4)
 *    b. Encontra cadeira livre entre as compatíveis (Regras 1+2+4)
 * 5. Cria o agendamento com status "solicitado"
 */
final class CriarAgendamentoUseCase
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly AgendamentoRepositoryInterface $agendamentoRepo,
        private readonly BarbeiroHorarioRepositoryInterface $horarioRepo,
        private readonly CadeiraRepositoryInterface $cadeiraRepo,
        private readonly ServicoRepositoryInterface $servicoRepo,
    ) {
    }

    /**
     * @throws ForaDaJanelaDeTrabalhoException
     * @throws CadeiraIncompativelException
     * @throws ConflitoDeHorarioException
     * @throws SlotIndisponivelException
     */
    public function executar(
        int $clienteId,
        int $barbeiroId,
        int $servicoId,
        DateTimeImmutable $horaInicio,
    ): Agendamento {
        // 1. Buscar serviço para calcular duração
        $servico = $this->servicoRepo->buscarPorId($servicoId);
        if ($servico === null) {
            throw new \InvalidArgumentException("Serviço #{$servicoId} não encontrado.");
        }

        // Calcular hora_fim
        $horaFim = $horaInicio->modify("+{$servico->duracaoMinutos} minutes");

        // 2. Regra 3: Verificar janela de trabalho do barbeiro
        $diaSemana = DiaSemana::fromDateTime($horaInicio);
        $horario = $this->horarioRepo->buscarPorBarbeiroEDia($barbeiroId, $diaSemana);

        if ($horario === null) {
            throw new ForaDaJanelaDeTrabalhoException($barbeiroId, $diaSemana->label());
        }

        $horaInicioStr = $horaInicio->format('H:i');
        $horaFimStr = $horaFim->format('H:i');

        if (!$horario->contemIntervalo($horaInicioStr, $horaFimStr)) {
            throw new ForaDaJanelaDeTrabalhoException($barbeiroId, $diaSemana->label());
        }

        // 3. Regra 2: Buscar cadeiras compatíveis com o serviço
        $cadeirasCompativeis = $this->cadeiraRepo->listarCompativeisComServico($servicoId);

        if (empty($cadeirasCompativeis)) {
            throw new CadeiraIncompativelException($servicoId);
        }

        // 4. Transação com locking para garantir concorrência (Regra 5)
        $iniciouTransacao = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $iniciouTransacao = true;
        }

        try {
            // 4a. Regra 1+4: Verificar conflito de barbeiro
            if ($this->agendamentoRepo->existeConflitoBarbeiro($barbeiroId, $horaInicio, $horaFim)) {
                throw new ConflitoDeHorarioException('barbeiro', $barbeiroId);
            }

            // 4b. Regra 1+2+4: Encontrar cadeira compatível e livre
            $cadeiraLivreId = null;

            foreach ($cadeirasCompativeis as $cadeira) {
                if (!$this->agendamentoRepo->existeConflitoCadeira($cadeira->id, $horaInicio, $horaFim)) {
                    $cadeiraLivreId = $cadeira->id;
                    break;
                }
            }

            if ($cadeiraLivreId === null) {
                throw new SlotIndisponivelException(
                    'Todas as cadeiras compatíveis com o serviço estão ocupadas no horário solicitado.'
                );
            }

            // 5. Criar e persistir o agendamento
            $agendamento = Agendamento::criar(
                clienteId: $clienteId,
                barbeiroId: $barbeiroId,
                cadeiraId: $cadeiraLivreId,
                servicoId: $servicoId,
                horaInicio: $horaInicio,
                horaFim: $horaFim,
            );

            $agendamento = $this->agendamentoRepo->salvar($agendamento);

            if ($iniciouTransacao && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            return $agendamento;
        } catch (\Throwable $e) {
            if ($iniciouTransacao && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
