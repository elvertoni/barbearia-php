<?php

declare(strict_types=1);

namespace App\Http\Auth;

use App\Domain\Enum\PerfilUsuario;

/**
 * Contrato mínimo que os middlewares de autenticação/autorização consomem.
 * Permite substituir a sessão real por um stub nos testes.
 */
interface ContextoAutenticacao
{
    public function estaLogada(): bool;

    public function perfil(): ?PerfilUsuario;
}
