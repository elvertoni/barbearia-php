<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Agendamento;
use DateTimeImmutable;

/**
 * Repositório de Agendamentos — contém os métodos críticos de verificação de conflito.
 *
 * As queries de conflito (Regra 4 do PRD) usam lógica de sobreposição de intervalos em SQL:
 *   hora_inicio < :novo_fim AND hora_fim > :novo_inicio
 *
 * A implementação DEVE usar SELECT ... FOR UPDATE para garantir concorrência (Regra 5).
 */
interface AgendamentoRepositoryInterface
{
    /**
     * Verifica se existe conflito de horário para o barbeiro no intervalo dado.
     * Exclui agendamentos com status 'cancelado' e 'no_show'.
     * Deve usar locking (FOR UPDATE) quando chamado dentro de transação.
     */
    public function existeConflitoBarbeiro(
        int $barbeiroId,
        DateTimeImmutable $horaInicio,
        DateTimeImmutable $horaFim,
        ?int $excluirAgendamentoId = null,
    ): bool;

    /**
     * Verifica se existe conflito de horário para a cadeira no intervalo dado.
     * Exclui agendamentos com status 'cancelado' e 'no_show'.
     * Deve usar locking (FOR UPDATE) quando chamado dentro de transação.
     */
    public function existeConflitoCadeira(
        int $cadeiraId,
        DateTimeImmutable $horaInicio,
        DateTimeImmutable $horaFim,
        ?int $excluirAgendamentoId = null,
    ): bool;

    public function salvar(Agendamento $agendamento): Agendamento;

    public function buscarPorId(int $id): ?Agendamento;

    /**
     * Lista agendamentos de um barbeiro em um dia específico.
     *
     * @return Agendamento[]
     */
    public function listarPorBarbeiroEDia(int $barbeiroId, string $data): array;

    /**
     * Lista todos os agendamentos de um dia específico.
     *
     * @return Agendamento[]
     */
    public function listarPorDia(string $data): array;

    public function atualizar(Agendamento $agendamento): void;
}
