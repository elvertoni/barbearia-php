<?php

declare(strict_types=1);

namespace App\Http\Auth;

use App\Domain\Entity\Usuario;
use App\Domain\Enum\PerfilUsuario;
use App\Domain\Repository\UsuarioRepositoryInterface;

/**
 * Wrapper em torno de $_SESSION — estado de autenticação da requisição.
 *
 * Guarda só o essencial na sessão (id, perfil, nome); a entidade Usuario
 * completa é carregada sob demanda via repositório, com cache na instância.
 * Também gerencia o token CSRF da sessão.
 */
final class SessaoAtual implements ContextoAutenticacao
{
    private const CHAVE_ID = 'usuario_id';
    private const CHAVE_PERFIL = 'usuario_perfil';
    private const CHAVE_NOME = 'usuario_nome';
    private const CHAVE_CSRF = '_csrf';

    private ?Usuario $usuarioCache = null;

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarioRepo,
    ) {
    }

    public function login(Usuario $usuario): void
    {
        session_regenerate_id(true);
        $_SESSION[self::CHAVE_ID] = $usuario->id;
        $_SESSION[self::CHAVE_PERFIL] = $usuario->perfil->value;
        $_SESSION[self::CHAVE_NOME] = $usuario->nome;
        $this->usuarioCache = $usuario;
    }

    public function logout(): void
    {
        $_SESSION = [];
        $this->usuarioCache = null;

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ],
            );
        }

        session_destroy();
    }

    public function estaLogada(): bool
    {
        return isset($_SESSION[self::CHAVE_ID]);
    }

    public function usuarioId(): ?int
    {
        return isset($_SESSION[self::CHAVE_ID]) ? (int) $_SESSION[self::CHAVE_ID] : null;
    }

    public function nome(): ?string
    {
        return $_SESSION[self::CHAVE_NOME] ?? null;
    }

    public function perfil(): ?PerfilUsuario
    {
        $valor = $_SESSION[self::CHAVE_PERFIL] ?? null;

        return $valor !== null ? PerfilUsuario::from($valor) : null;
    }

    /**
     * Carrega a entidade Usuario completa (com cache na instância).
     */
    public function usuario(): ?Usuario
    {
        if ($this->usuarioCache !== null) {
            return $this->usuarioCache;
        }

        $id = $this->usuarioId();

        if ($id === null) {
            return null;
        }

        return $this->usuarioCache = $this->usuarioRepo->buscarPorId($id);
    }

    public function tokenCsrf(): string
    {
        if (empty($_SESSION[self::CHAVE_CSRF])) {
            $_SESSION[self::CHAVE_CSRF] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CHAVE_CSRF];
    }

    public function validarCsrf(?string $token): bool
    {
        $esperado = $_SESSION[self::CHAVE_CSRF] ?? null;

        return is_string($token) && is_string($esperado) && hash_equals($esperado, $token);
    }
}
