<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\FilaEspera;
use App\Domain\Repository\FilaEsperaRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class PdoFilaEsperaRepository implements FilaEsperaRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function salvar(FilaEspera $filaEspera): FilaEspera
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO fila_espera (cliente_id, servico_id, data_desejada, criado_em)
            VALUES (:cliente_id, :servico_id, :data_desejada, :criado_em)
        ');
        $stmt->execute([
            'cliente_id' => $filaEspera->clienteId,
            'servico_id' => $filaEspera->servicoId,
            'data_desejada' => $filaEspera->dataDesejada,
            'criado_em' => $filaEspera->criadoEm->format('Y-m-d H:i:s'),
        ]);

        return $filaEspera->comId((int) $this->pdo->lastInsertId());
    }

    public function listarPorDataEServico(string $data, int $servicoId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM fila_espera
            WHERE data_desejada = :data AND servico_id = :servico_id
            ORDER BY criado_em ASC
        ');
        $stmt->execute([
            'data' => $data,
            'servico_id' => $servicoId,
        ]);

        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    public function listarPorData(string $data): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM fila_espera
            WHERE data_desejada = :data
            ORDER BY criado_em ASC
        ');
        $stmt->execute(['data' => $data]);

        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    public function remover(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM fila_espera WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function hidratar(array $row): FilaEspera
    {
        return new FilaEspera(
            id: (int) $row['id'],
            clienteId: (int) $row['cliente_id'],
            servicoId: (int) $row['servico_id'],
            dataDesejada: $row['data_desejada'],
            criadoEm: new DateTimeImmutable($row['criado_em'], new DateTimeZone('UTC')),
        );
    }
}
