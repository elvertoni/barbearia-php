<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\UseCase\RegistrarUsuarioUseCase;
use App\Application\UseCase\VincularUsuarioBarbeiroUseCase;
use App\Domain\Enum\PerfilUsuario;
use App\Domain\Repository\BarbeiroRepositoryInterface;
use App\Domain\Repository\UsuarioRepositoryInterface;
use App\Http\Auth\SessaoAtual;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;

/**
 * Controller de gestão de usuários — restrito ao perfil dono.
 */
final class UsuarioController
{
    public function __construct(
        private readonly RegistrarUsuarioUseCase $registrarUseCase,
        private readonly VincularUsuarioBarbeiroUseCase $vincularUseCase,
        private readonly UsuarioRepositoryInterface $usuarioRepo,
        private readonly BarbeiroRepositoryInterface $barbeiroRepo,
        private readonly SessaoAtual $sessao,
    ) {
    }

    public function index(Request $request): Response
    {
        $html = View::render('usuarios/index', [
            'usuarios' => $this->usuarioRepo->listarTodos(),
            'barbeirosSemUsuario' => array_filter(
                $this->barbeiroRepo->listarTodos(),
                fn($b) => $b->usuarioId === null,
            ),
            'perfis' => PerfilUsuario::cases(),
        ]);

        return (new Response())->html($html);
    }

    public function store(Request $request): Response
    {
        $nome = trim((string) $request->input('nome', ''));
        $email = trim((string) $request->input('email', ''));
        $senha = (string) $request->input('senha', '');
        $perfilRaw = (string) $request->input('perfil', '');
        $barbeiroId = (int) $request->input('barbeiro_id', 0);

        if ($nome === '' || $email === '' || strlen($senha) < 8 || $perfilRaw === '') {
            return (new Response())->redirect(
                '/usuarios?erro=' . urlencode('Preencha todos os campos (senha com no mínimo 8 caracteres).')
            );
        }

        try {
            $perfil = PerfilUsuario::from($perfilRaw);
            $usuario = $this->registrarUseCase->executar($nome, $email, $senha, $perfil);

            if ($perfil === PerfilUsuario::Barbeiro && $barbeiroId > 0) {
                $this->vincularUseCase->executar($barbeiroId, $usuario->id);
            }

            return (new Response())->redirect('/usuarios?sucesso=' . urlencode('Usuário criado.'));
        } catch (\Throwable $e) {
            return (new Response())->redirect('/usuarios?erro=' . urlencode($e->getMessage()));
        }
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');

        if ($id === $this->sessao->usuarioId()) {
            return (new Response())->redirect('/usuarios?erro=' . urlencode('Você não pode remover o próprio usuário.'));
        }

        try {
            $this->usuarioRepo->excluir($id);

            return (new Response())->redirect('/usuarios?sucesso=' . urlencode('Usuário removido.'));
        } catch (\Throwable $e) {
            return (new Response())->redirect('/usuarios?erro=' . urlencode($e->getMessage()));
        }
    }
}
