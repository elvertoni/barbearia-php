<?php

declare(strict_types=1);

/**
 * Script runner de migrations SQL.
 *
 * Lê arquivos .sql numerados em database/migrations/, rastreia quais já
 * foram executados (tabela `migrations`), e executa os pendentes em ordem.
 *
 * Uso:
 *   php database/migrate.php
 *   docker compose exec app php database/migrate.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Database\Connection;

$pdo = Connection::create();

// Criar tabela de controle de migrations (se não existir)
$pdo->exec('
    CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
');

// Buscar migrations já executadas
$stmt = $pdo->query('SELECT filename FROM migrations ORDER BY filename');
$executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Listar arquivos de migration disponíveis
$migrationsDir = __DIR__ . '/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

$pending = 0;

foreach ($files as $file) {
    $filename = basename($file);

    if (in_array($filename, $executed, true)) {
        echo "  [OK] {$filename} (já executada)\n";
        continue;
    }

    $sql = file_get_contents($file);

    if ($sql === false || trim($sql) === '') {
        echo "  [SKIP] {$filename} (arquivo vazio ou ilegível)\n";
        continue;
    }

    try {
        $pdo->beginTransaction();
        $pdo->exec($sql);
        $pdo->prepare('INSERT INTO migrations (filename) VALUES (:filename)')
            ->execute(['filename' => $filename]);
        $pdo->commit();

        echo "  [RUN] {$filename} ✓\n";
        $pending++;
    } catch (Throwable $e) {
        $pdo->rollBack();
        echo "  [FAIL] {$filename}: {$e->getMessage()}\n";
        exit(1);
    }
}

if ($pending === 0) {
    echo "\nNenhuma migration pendente.\n";
} else {
    echo "\n{$pending} migration(s) executada(s) com sucesso.\n";
}
