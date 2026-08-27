<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Fábrica de conexão PDO.
 *
 * Lê credenciais de variáveis de ambiente (definidas via Docker Compose
 * ou arquivo .env) e retorna uma instância configurada de PDO.
 */
final class Connection
{
    /**
     * Cria e retorna uma nova conexão PDO com MySQL.
     *
     * Configurações aplicadas:
     * - ERRMODE_EXCEPTION: erros de SQL lançam exceção (fail-fast)
     * - FETCH_ASSOC: fetch retorna arrays associativos por padrão
     * - EMULATE_PREPARES desabilitado: usa prepared statements nativos do MySQL
     *
     * @throws RuntimeException se variáveis de ambiente obrigatórias estiverem ausentes
     * @throws PDOException se a conexão falhar
     */
    public static function create(): PDO
    {
        $host = self::env('DB_HOST');
        $port = self::env('DB_PORT', '3306');
        $name = self::env('DB_NAME');
        $user = self::env('DB_USER');
        $pass = self::env('DB_PASS');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'",
        ]);

        return $pdo;
    }

    /**
     * Lê uma variável de ambiente, lançando exceção se obrigatória e ausente.
     */
    private static function env(string $key, ?string $default = null): string
    {
        $value = getenv($key);

        if ($value === false) {
            if ($default !== null) {
                return $default;
            }
            throw new RuntimeException(
                sprintf('Variável de ambiente obrigatória "%s" não definida.', $key)
            );
        }

        return $value;
    }
}
