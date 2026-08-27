<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Servico;
use App\Domain\Repository\ServicoRepositoryInterface;
use PDO;

final class PdoServicoRepository implements ServicoRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function salvar(Servico $servico): Servico
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO servicos (nome, duracao_minutos, preco_centavos, ativo)
            VALUES (:nome, :duracao_minutos, :preco_centavos, :ativo)
        ');
        $stmt->execute([
            'nome' => $servico->nome,
            'duracao_minutos' => $servico->duracaoMinutos,
            'preco_centavos' => $servico->precoCentavos,
            'ativo' => $servico->ativo ? 1 : 0,
        ]);

        return $servico->comId((int) $this->pdo->lastInsertId());
    }

    public function buscarPorId(int $id): ?Servico
    {
        $stmt = $this->pdo->prepare('SELECT * FROM servicos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hidratar($row) : null;
    }

    public function listarAtivos(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM servicos WHERE ativo = 1 ORDER BY nome');
        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    public function listarTodos(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM servicos ORDER BY nome');
        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    public function atualizar(Servico $servico): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE servicos SET nome = :nome, duracao_minutos = :duracao_minutos,
            preco_centavos = :preco_centavos, ativo = :ativo WHERE id = :id
        ');
        $stmt->execute([
            'nome' => $servico->nome,
            'duracao_minutos' => $servico->duracaoMinutos,
            'preco_centavos' => $servico->precoCentavos,
            'ativo' => $servico->ativo ? 1 : 0,
            'id' => $servico->id,
        ]);
    }

    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM servicos WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    private function hidratar(array $row): Servico
    {
        return new Servico(
            id: (int) $row['id'],
            nome: $row['nome'],
            duracaoMinutos: (int) $row['duracao_minutos'],
            precoCentavos: (int) $row['preco_centavos'],
            ativo: (bool) $row['ativo'],
        );
    }
}
