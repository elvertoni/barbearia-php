<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\UseCase\AutenticarUsuarioUseCase;
use App\Domain\Enum\PerfilUsuario;
use App\Http\Auth\SessaoAtual;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;

/**
 * Controller de autenticação — login e logout.
 */
final class AuthController
{
    public function __construct(
        private readonly AutenticarUsuarioUseCase $autenticarUseCase,
        private readonly SessaoAtual $sessao,
    ) {
    }

    public function showLogin(Request $request): Response
    {
        if ($this->sessao->estaLogada()) {
            return (new Response())->redirect($this->destinoPorPerfil($this->sessao->perfil()));
        }

        return (new Response())->html(View::render('auth/login'));
    }

    public function login(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));
        $senha = (string) $request->input('senha', '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        try {
            $usuario = $this->autenticarUseCase->executar($email, $senha, $ip);
            $this->sessao->login($usuario);

            return (new Response())->redirect($this->destinoPorPerfil($usuario->perfil));
        } catch (\Throwable $e) {
            return (new Response())->redirect('/login?erro=' . urlencode($e->getMessage()));
        }
    }

    public function logout(Request $request): Response
    {
        $this->sessao->logout();

        return (new Response())->redirect('/login?sucesso=' . urlencode('Sessão encerrada.'));
    }

    private function destinoPorPerfil(?PerfilUsuario $perfil): string
    {
        return $perfil === PerfilUsuario::Barbeiro ? '/minha-agenda' : '/agendamentos';
    }
}
