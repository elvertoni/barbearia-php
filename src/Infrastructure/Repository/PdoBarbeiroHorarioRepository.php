<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\BarbeiroHorario;
use App\Domain\Enum\DiaSemana;
use App\Domain\Repository\BarbeiroHorarioRepositoryInterface;
use PDO;

final class PdoBarbeiroHorarioRepository implements BarbeiroHorarioRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function salvar(BarbeiroHorario $horario): BarbeiroHorario
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO barbeiro_horarios (barbeiro_id, dia_semana, hora_inicio, hora_fim)
            VALUES (:barbeiro_id, :dia_semana, :hora_inicio, :hora_fim)
        ');
        $stmt->execute([
            'barbeiro_id' => $horario->barbeiroId,
            'dia_semana' => $horario->diaSemana->value,
            'hora_inicio' => $horario->horaInicio,
            'hora_fim' => $horario->horaFim,
        ]);

        return new BarbeiroHorario(
            id: (int) $this->pdo->lastInsertId(),
            barbeiroId: $horario->barbeiroId,
            diaSemana: $horario->diaSemana,
            horaInicio: $horario->horaInicio,
            horaFim: $horario->horaFim,
        );
    }

    public function buscarPorBarbeiroEDia(int $barbeiroId, DiaSemana $diaSemana): ?BarbeiroHorario
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM barbeiro_horarios
            WHERE barbeiro_id = :barbeiro_id AND dia_semana = :dia_semana
        ');
        $stmt->execute([
            'barbeiro_id' => $barbeiroId,
            'dia_semana' => $diaSemana->value,
        ]);
        $row = $stmt->fetch();

        return $row ? $this->hidratar($row) : null;
    }

    public function listarPorBarbeiro(int $barbeiroId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM barbeiro_horarios
            WHERE barbeiro_id = :barbeiro_id
            ORDER BY dia_semana
        ');
        $stmt->execute(['barbeiro_id' => $barbeiroId]);

        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM barbeiro_horarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function excluirPorBarbeiro(int $barbeiroId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM barbeiro_horarios WHERE barbeiro_id = :barbeiro_id');
        $stmt->execute(['barbeiro_id' => $barbeiroId]);
    }

    private function hidratar(array $row): BarbeiroHorario
    {
        return new BarbeiroHorario(
            id: (int) $row['id'],
            barbeiroId: (int) $row['barbeiro_id'],
            diaSemana: DiaSemana::from((int) $row['dia_semana']),
            horaInicio: $row['hora_inicio'],
            horaFim: $row['hora_fim'],
        );
    }
}
