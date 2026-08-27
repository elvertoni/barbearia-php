<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\StatusAgendamento;
use App\Domain\Exception\TransicaoInvalidaException;
use DateTimeImmutable;

/**
 * Entidade Agendamento — contém a máquina de estados (Regra 6 do PRD).
 *
 * Transições válidas:
 *   solicitado → confirmado → em_atendimento → concluido
 *   solicitado → cancelado
 *   confirmado → cancelado
 *   confirmado → no_show
 */
final class Agendamento
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $clienteId,
        public readonly int $barbeiroId,
        public readonly int $cadeiraId,
        public readonly int $servicoId,
        public readonly DateTimeImmutable $horaInicio,
        public readonly DateTimeImmutable $horaFim,
        private StatusAgendamento $status,
        public readonly DateTimeImmutable $criadoEm,
    ) {
    }

    /**
     * Cria um novo agendamento com status inicial "solicitado".
     */
    public static function criar(
        int $clienteId,
        int $barbeiroId,
        int $cadeiraId,
        int $servicoId,
        DateTimeImmutable $horaInicio,
        DateTimeImmutable $horaFim,
    ): self {
        return new self(
            id: null,
            clienteId: $clienteId,
            barbeiroId: $barbeiroId,
            cadeiraId: $cadeiraId,
            servicoId: $servicoId,
            horaInicio: $horaInicio,
            horaFim: $horaFim,
            status: StatusAgendamento::Solicitado,
            criadoEm: new DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );
    }

    /**
     * Reconstrói a entidade a partir de dados do banco (sem validação de transição).
     */
    public static function reconstituir(
        int $id,
        int $clienteId,
        int $barbeiroId,
        int $cadeiraId,
        int $servicoId,
        DateTimeImmutable $horaInicio,
        DateTimeImmutable $horaFim,
        StatusAgendamento $status,
        DateTimeImmutable $criadoEm,
    ): self {
        return new self(
            id: $id,
            clienteId: $clienteId,
            barbeiroId: $barbeiroId,
            cadeiraId: $cadeiraId,
            servicoId: $servicoId,
            horaInicio: $horaInicio,
            horaFim: $horaFim,
            status: $status,
            criadoEm: $criadoEm,
        );
    }

    public function status(): StatusAgendamento
    {
        return $this->status;
    }

    /**
     * Transita o status do agendamento, validando a máquina de estados.
     *
     * @throws TransicaoInvalidaException se a transição não for permitida
     */
    public function transitar(StatusAgendamento $novoStatus): void
    {
        if (!$this->status->podeTransitarPara($novoStatus)) {
            throw new TransicaoInvalidaException(
                $this->status->value,
                $novoStatus->value,
            );
        }

        $this->status = $novoStatus;
    }

    public function comId(int $id): self
    {
        return new self(
            id: $id,
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            cadeiraId: $this->cadeiraId,
            servicoId: $this->servicoId,
            horaInicio: $this->horaInicio,
            horaFim: $this->horaFim,
            status: $this->status,
            criadoEm: $this->criadoEm,
        );
    }
}
