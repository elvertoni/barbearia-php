<?php

declare(strict_types=1);

/**
 * Seed inicial: cria o primeiro usuário (perfil dono).
 *
 * Lê ADMIN_NOME, ADMIN_EMAIL e ADMIN_SENHA do ambiente. Idempotente —
 * se já existe usuário com esse e-mail, não faz nada.
 *
 * Uso:
 *   docker compose exec app php database/seed.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Application\UseCase\RegistrarUsuarioUseCase;
use App\Domain\Enum\PerfilUsuario;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repository\PdoUsuarioRepository;

function env(string $chave): string
{
    $valor = getenv($chave);

    if ($valor === false || trim($valor) === '') {
        fwrite(STDERR, "Variável de ambiente obrigatória \"{$chave}\" não definida.\n");
        exit(1);
    }

    return $valor;
}

$nome = env('ADMIN_NOME');
$email = mb_strtolower(trim(env('ADMIN_EMAIL')));
$senha = env('ADMIN_SENHA');

$pdo = Connection::create();
$usuarioRepo = new PdoUsuarioRepository($pdo);

if ($usuarioRepo->buscarPorEmail($email) !== null) {
    echo "Usuário \"{$email}\" já existe — nada a fazer.\n";
    exit(0);
}

$registrar = new RegistrarUsuarioUseCase($usuarioRepo);
$usuario = $registrar->executar($nome, $email, $senha, PerfilUsuario::Dono);

echo "Usuário dono criado: #{$usuario->id} <{$usuario->email}>\n";
