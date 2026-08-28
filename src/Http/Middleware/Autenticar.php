<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Auth\ContextoAutenticacao;
use App\Http\Request;
use App\Http\Response;

/**
 * Middleware: exige sessão autenticada. Sem sessão → redireciona para /login.
 */
final class Autenticar
{
    public function __construct(private readonly ContextoAutenticacao $sessao)
    {
    }

    public function __invoke(Request $request): ?Response
    {
        if (!$this->sessao->estaLogada()) {
            return (new Response())->redirect('/login?erro=' . urlencode('Faça login para continuar.'));
        }

        return null;
    }
}
