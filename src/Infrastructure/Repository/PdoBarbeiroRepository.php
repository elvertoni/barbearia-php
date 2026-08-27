<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Barbeiro;
use App\Domain\Repository\BarbeiroRepositoryInterface;
use PDO;

final class PdoBarbeiroRepository implements BarbeiroRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    public function salvar(Barbeiro $barbeiro): Barbeiro
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO barbeiros (nome, ativo) VALUES (:nome, :ativo)
        ');
        $stmt->execute([
            'nome' => $barbeiro->nome,
            'ativo' => $barbeiro->ativo ? 1 : 0,
        ]);

        return $barbeiro->comId((int) $this->pdo->lastInsertId());
    }

    public function buscarPorId(int $id): ?Barbeiro
    {
        $stmt = $this->pdo->prepare('SELECT * FROM barbeiros WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hidratar($row) : null;
    }

    public function listarAtivos(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM barbeiros WHERE ativo = 1 ORDER BY nome');
        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    public function listarTodos(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM barbeiros ORDER BY nome');
        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    public function atualizar(Barbeiro $barbeiro): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE barbeiros SET nome = :nome, ativo = :ativo WHERE id = :id
        ');
        $stmt->execute([
            'nome' => $barbeiro->nome,
            'ativo' => $barbeiro->ativo ? 1 : 0,
            'id' => $barbeiro->id,
        ]);
    }

    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM barbeiros WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function hidratar(array $row): Barbeiro
    {
        return new Barbeiro(
            id: (int) $row['id'],
            nome: $row['nome'],
            ativo: (bool) $row['ativo'],
        );
    }
}
