<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\Usuario;
use App\Domain\Enum\PerfilUsuario;
use App\Domain\Repository\UsuarioRepositoryInterface;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class PdoUsuarioRepository implements UsuarioRepositoryInterface
{
    private const UTC = 'UTC';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function salvar(Usuario $usuario): Usuario
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO usuarios (nome, email, senha_hash, perfil, ativo)
            VALUES (:nome, :email, :senha_hash, :perfil, :ativo)
        ');
        $stmt->execute([
            'nome' => $usuario->nome,
            'email' => $usuario->email,
            'senha_hash' => $usuario->senhaHash,
            'perfil' => $usuario->perfil->value,
            'ativo' => $usuario->ativo ? 1 : 0,
        ]);

        return $usuario->comId((int) $this->pdo->lastInsertId());
    }

    public function buscarPorId(int $id): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hidratar($row) : null;
    }

    public function buscarPorEmail(string $email): ?Usuario
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? $this->hidratar($row) : null;
    }

    public function listarTodos(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM usuarios ORDER BY nome');
        return array_map([$this, 'hidratar'], $stmt->fetchAll());
    }

    public function atualizar(Usuario $usuario): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE usuarios
            SET nome = :nome, email = :email, senha_hash = :senha_hash, perfil = :perfil, ativo = :ativo
            WHERE id = :id
        ');
        $stmt->execute([
            'nome' => $usuario->nome,
            'email' => $usuario->email,
            'senha_hash' => $usuario->senhaHash,
            'perfil' => $usuario->perfil->value,
            'ativo' => $usuario->ativo ? 1 : 0,
            'id' => $usuario->id,
        ]);
    }

    public function excluir(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function contarTodos(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    }

    private function hidratar(array $row): Usuario
    {
        $tz = new DateTimeZone(self::UTC);

        return Usuario::reconstituir(
            id: (int) $row['id'],
            nome: $row['nome'],
            email: $row['email'],
            senhaHash: $row['senha_hash'],
            perfil: PerfilUsuario::from($row['perfil']),
            ativo: (bool) $row['ativo'],
            criadoEm: new DateTimeImmutable($row['criado_em'], $tz),
        );
    }
}
