<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Repository\TentativaLoginRepositoryInterface;
use PDO;

final class PdoTentativaLoginRepository implements TentativaLoginRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function registrar(string $email, string $ip, bool $sucesso): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO login_tentativas (email, ip, sucesso)
            VALUES (:email, :ip, :sucesso)
        ');
        $stmt->execute([
            'email' => $email,
            'ip' => $ip,
            'sucesso' => $sucesso ? 1 : 0,
        ]);
    }

    public function contarFalhasRecentes(string $email, int $minutos): int
    {
        // $minutos é int controlado internamente — interpolado direto para evitar
        // placeholder dentro de INTERVAL. O e-mail continua parametrizado.
        $sql = sprintf(
            'SELECT COUNT(*) FROM login_tentativas
             WHERE email = :email
               AND sucesso = 0
               AND criado_em >= (UTC_TIMESTAMP() - INTERVAL %d MINUTE)',
            $minutos,
        );
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        return (int) $stmt->fetchColumn();
    }
}
