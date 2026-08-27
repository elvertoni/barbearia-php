<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Cliente;
use App\Domain\Repository\ClienteRepositoryInterface;
use PDO;

final class PdoClienteRepository implements ClienteRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function salvar(Cliente $cliente): Cliente
    {
        $stmt = $this->pdo->prepare('INSERT INTO clientes (nome, telefone) VALUES (:nome, :telefone)');
        $stmt->execute([
            'nome' => $cliente->nome,
            'telefone' => $cliente->telefone,
        ]);

        return $cliente->comId((int) $this->pdo->lastInsertId());
    }

    public function buscarPorId(int $id): ?Cliente
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clientes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hidratar($row) : null;
    }

    public function buscarPorTelefone(string $telefone): ?Cliente
    {
        $stmt = $this->pdo->prepare('SELECT * FROM clientes WHERE telefone = :telefone');
        $stmt->execute(['telefone' => $telefone]);
        $row = $stmt->fetch();

        return $row ? $this->hidratar($row) : null;
    }

    public function listarTodos(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM clientes ORDER BY nome');
        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    public function atualizar(Cliente $cliente): void
    {
        $stmt = $this->pdo->prepare('UPDATE clientes SET nome = :nome, telefone = :telefone WHERE id = :id');
        $stmt->execute([
            'nome' => $cliente->nome,
            'telefone' => $cliente->telefone,
            'id' => $cliente->id,
        ]);
    }

    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM clientes WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function hidratar(array $row): Cliente
    {
        return new Cliente(
            id: (int) $row['id'],
            nome: $row['nome'],
            telefone: $row['telefone'],
        );
    }
}
