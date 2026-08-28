<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Enum\PerfilUsuario;
use App\Http\Auth\ContextoAutenticacao;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;

/**
 * Middleware: exige que o perfil da sessão esteja entre os permitidos.
 *
 * Deve rodar depois de Autenticar. Perfil ausente ou fora da lista → 403.
 */
final class AutorizarPerfil
{
    /** @var list<PerfilUsuario> */
    private readonly array $permitidos;

    public function __construct(
        private readonly ContextoAutenticacao $sessao,
        PerfilUsuario ...$permitidos,
    ) {
        $this->permitidos = $permitidos;
    }

    public function __invoke(Request $request): ?Response
    {
        $perfil = $this->sessao->perfil();

        if ($perfil === null || !in_array($perfil, $this->permitidos, true)) {
            return (new Response())->html(View::render('erros/403'), 403);
        }

        return null;
    }
}
