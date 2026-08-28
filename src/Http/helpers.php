<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Funções auxiliares de view. Carregadas via autoload `files` do Composer.
 *
 * Leem $_SESSION diretamente porque as views montam o próprio array de
 * variáveis e não recebem a SessaoAtual injetada.
 */

/**
 * Token CSRF da sessão atual (cria na primeira chamada).
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

/**
 * Campo hidden com o token CSRF, pronto para colar dentro de <form>.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

/**
 * Dados do usuário logado para a view (perfil + nome), ou null.
 *
 * @return array{id: int, perfil: string, nome: string}|null
 */
function usuario_logado(): ?array
{
    if (!isset($_SESSION['usuario_id'])) {
        return null;
    }

    return [
        'id' => (int) $_SESSION['usuario_id'],
        'perfil' => (string) ($_SESSION['usuario_perfil'] ?? ''),
        'nome' => (string) ($_SESSION['usuario_nome'] ?? ''),
    ];
}
