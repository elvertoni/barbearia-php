<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Database\Connection;

// Carregar variáveis de ambiente do Docker (já definidas via docker-compose)
// Em produção, usar .env loader ou variáveis de sistema

try {
    $pdo = Connection::create();
    
    // Resposta simples para verificar que o sistema está funcionando
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'ok',
        'message' => 'Barbearia API está funcionando',
        'php_version' => PHP_VERSION,
        'timestamp' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('c'),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'Falha ao inicializar: ' . $e->getMessage(),
    ]);
}
