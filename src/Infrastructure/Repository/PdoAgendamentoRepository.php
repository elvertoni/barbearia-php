<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Agendamento;
use App\Domain\Enum\StatusAgendamento;
use App\Domain\Repository\AgendamentoRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

/**
 * Implementação PDO do repositório de Agendamentos.
 *
 * Contém as queries críticas de verificação de conflito (Regra 4 do PRD).
 * Usa SELECT ... FOR UPDATE para concorrência (Regra 5).
 */
final class PdoAgendamentoRepository implements AgendamentoRepositoryInterface
{
    private const UTC = 'UTC';

    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /**
     * Regra 4: Verificação de conflito de barbeiro via SQL com lógica de intervalos.
     * Regra 5: Usa FOR UPDATE para locking de concorrência.
     *
     * Fórmula de sobreposição: A_inicio < B_fim AND A_fim > B_inicio
     */
    public function existeConflitoBarbeiro(
        int $barbeiroId,
        DateTimeImmutable $horaInicio,
        DateTimeImmutable $horaFim,
        ?int $excluirAgendamentoId = null,
    ): bool {
        $sql = '
            SELECT COUNT(*) FROM agendamentos
            WHERE barbeiro_id = :barbeiro_id
              AND status NOT IN (:cancelado, :no_show)
              AND hora_inicio < :novo_fim
              AND hora_fim > :novo_inicio
        ';

        if ($excluirAgendamentoId !== null) {
            $sql .= ' AND id != :excluir_id';
        }

        $sql .= ' FOR UPDATE';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':barbeiro_id', $barbeiroId, PDO::PARAM_INT);
        $stmt->bindValue(':cancelado', StatusAgendamento::Cancelado->value);
        $stmt->bindValue(':no_show', StatusAgendamento::NoShow->value);
        $stmt->bindValue(':novo_fim', $horaFim->format('Y-m-d H:i:s'));
        $stmt->bindValue(':novo_inicio', $horaInicio->format('Y-m-d H:i:s'));

        if ($excluirAgendamentoId !== null) {
            $stmt->bindValue(':excluir_id', $excluirAgendamentoId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Regra 4: Verificação de conflito de cadeira via SQL com lógica de intervalos.
     * Regra 5: Usa FOR UPDATE para locking de concorrência.
     */
    public function existeConflitoCadeira(
        int $cadeiraId,
        DateTimeImmutable $horaInicio,
        DateTimeImmutable $horaFim,
        ?int $excluirAgendamentoId = null,
    ): bool {
        $sql = '
            SELECT COUNT(*) FROM agendamentos
            WHERE cadeira_id = :cadeira_id
              AND status NOT IN (:cancelado, :no_show)
              AND hora_inicio < :novo_fim
              AND hora_fim > :novo_inicio
        ';

        if ($excluirAgendamentoId !== null) {
            $sql .= ' AND id != :excluir_id';
        }

        $sql .= ' FOR UPDATE';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':cadeira_id', $cadeiraId, PDO::PARAM_INT);
        $stmt->bindValue(':cancelado', StatusAgendamento::Cancelado->value);
        $stmt->bindValue(':no_show', StatusAgendamento::NoShow->value);
        $stmt->bindValue(':novo_fim', $horaFim->format('Y-m-d H:i:s'));
        $stmt->bindValue(':novo_inicio', $horaInicio->format('Y-m-d H:i:s'));

        if ($excluirAgendamentoId !== null) {
            $stmt->bindValue(':excluir_id', $excluirAgendamentoId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    public function salvar(Agendamento $agendamento): Agendamento
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO agendamentos (cliente_id, barbeiro_id, cadeira_id, servico_id, hora_inicio, hora_fim, status, criado_em)
            VALUES (:cliente_id, :barbeiro_id, :cadeira_id, :servico_id, :hora_inicio, :hora_fim, :status, :criado_em)
        ');

        $stmt->execute([
            'cliente_id' => $agendamento->clienteId,
            'barbeiro_id' => $agendamento->barbeiroId,
            'cadeira_id' => $agendamento->cadeiraId,
            'servico_id' => $agendamento->servicoId,
            'hora_inicio' => $agendamento->horaInicio->format('Y-m-d H:i:s'),
            'hora_fim' => $agendamento->horaFim->format('Y-m-d H:i:s'),
            'status' => $agendamento->status()->value,
            'criado_em' => $agendamento->criadoEm->format('Y-m-d H:i:s'),
        ]);

        return $agendamento->comId((int) $this->pdo->lastInsertId());
    }

    public function buscarPorId(int $id): ?Agendamento
    {
        $stmt = $this->pdo->prepare('SELECT * FROM agendamentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hidratarAgendamento($row) : null;
    }

    public function listarPorBarbeiroEDia(int $barbeiroId, string $data): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM agendamentos
            WHERE barbeiro_id = :barbeiro_id
              AND DATE(hora_inicio) = :data
            ORDER BY hora_inicio ASC
        ');

        $stmt->execute([
            'barbeiro_id' => $barbeiroId,
            'data' => $data,
        ]);

        return array_map([$this, 'hidratarAgendamento'], $stmt->fetchAll());
    }

    public function listarPorDia(string $data): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM agendamentos
            WHERE DATE(hora_inicio) = :data
            ORDER BY hora_inicio ASC
        ');

        $stmt->execute(['data' => $data]);

        return array_map([$this, 'hidratarAgendamento'], $stmt->fetchAll());
    }

    public function atualizar(Agendamento $agendamento): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE agendamentos
            SET status = :status
            WHERE id = :id
        ');

        $stmt->execute([
            'status' => $agendamento->status()->value,
            'id' => $agendamento->id,
        ]);
    }

    private function hidratarAgendamento(array $row): Agendamento
    {
        $tz = new DateTimeZone(self::UTC);

        return Agendamento::reconstituir(
            id: (int) $row['id'],
            clienteId: (int) $row['cliente_id'],
            barbeiroId: (int) $row['barbeiro_id'],
            cadeiraId: (int) $row['cadeira_id'],
            servicoId: (int) $row['servico_id'],
            horaInicio: new DateTimeImmutable($row['hora_inicio'], $tz),
            horaFim: new DateTimeImmutable($row['hora_fim'], $tz),
            status: StatusAgendamento::from($row['status']),
            criadoEm: new DateTimeImmutable($row['criado_em'], $tz),
        );
    }
}
