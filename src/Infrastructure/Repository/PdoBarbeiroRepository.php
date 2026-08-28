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
            INSERT INTO barbeiros (nome, usuario_id, ativo) VALUES (:nome, :usuario_id, :ativo)
        ');
        $stmt->execute([
            'nome' => $barbeiro->nome,
            'usuario_id' => $barbeiro->usuarioId,
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

    public function buscarPorUsuarioId(int $usuarioId): ?Barbeiro
    {
        $stmt = $this->pdo->prepare('SELECT * FROM barbeiros WHERE usuario_id = :usuario_id');
        $stmt->execute(['usuario_id' => $usuarioId]);
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
            UPDATE barbeiros SET nome = :nome, usuario_id = :usuario_id, ativo = :ativo WHERE id = :id
        ');
        $stmt->execute([
            'nome' => $barbeiro->nome,
            'usuario_id' => $barbeiro->usuarioId,
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
            usuarioId: $row['usuario_id'] !== null ? (int) $row['usuario_id'] : null,
            ativo: (bool) $row['ativo'],
        );
    }
}
