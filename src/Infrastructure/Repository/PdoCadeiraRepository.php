<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Cadeira;
use App\Domain\Repository\CadeiraRepositoryInterface;
use PDO;

final class PdoCadeiraRepository implements CadeiraRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function salvar(Cadeira $cadeira): Cadeira
    {
        $stmt = $this->pdo->prepare('INSERT INTO cadeiras (nome, ativo) VALUES (:nome, :ativo)');
        $stmt->execute([
            'nome' => $cadeira->nome,
            'ativo' => $cadeira->ativo ? 1 : 0,
        ]);

        return $cadeira->comId((int) $this->pdo->lastInsertId());
    }

    public function buscarPorId(int $id): ?Cadeira
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cadeiras WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hidratar($row) : null;
    }

    public function listarAtivas(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM cadeiras WHERE ativo = 1 ORDER BY nome');
        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    public function listarTodas(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM cadeiras ORDER BY nome');
        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    public function listarCompativeisComServico(int $servicoId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT c.* FROM cadeiras c
            INNER JOIN cadeira_servico_compativel csc ON csc.cadeira_id = c.id
            WHERE csc.servico_id = :servico_id
              AND c.ativo = 1
            ORDER BY c.nome
        ');
        $stmt->execute(['servico_id' => $servicoId]);

        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    public function atualizar(Cadeira $cadeira): void
    {
        $stmt = $this->pdo->prepare('UPDATE cadeiras SET nome = :nome, ativo = :ativo WHERE id = :id');
        $stmt->execute([
            'nome' => $cadeira->nome,
            'ativo' => $cadeira->ativo ? 1 : 0,
            'id' => $cadeira->id,
        ]);
    }

    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM cadeiras WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function vincularServico(int $cadeiraId, int $servicoId): void
    {
        $stmt = $this->pdo->prepare('
            INSERT IGNORE INTO cadeira_servico_compativel (cadeira_id, servico_id)
            VALUES (:cadeira_id, :servico_id)
        ');
        $stmt->execute([
            'cadeira_id' => $cadeiraId,
            'servico_id' => $servicoId,
        ]);
    }

    public function desvincularServico(int $cadeiraId, int $servicoId): void
    {
        $stmt = $this->pdo->prepare('
            DELETE FROM cadeira_servico_compativel
            WHERE cadeira_id = :cadeira_id AND servico_id = :servico_id
        ');
        $stmt->execute([
            'cadeira_id' => $cadeiraId,
            'servico_id' => $servicoId,
        ]);
    }

    private function hidratar(array $row): Cadeira
    {
        return new Cadeira(
            id: (int) $row['id'],
            nome: $row['nome'],
            ativo: (bool) $row['ativo'],
        );
    }
}
