<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Domain\Enum\PerfilUsuario;
use App\Http\Auth\ContextoAutenticacao;
use App\Http\Middleware\Autenticar;
use App\Http\Middleware\AutorizarPerfil;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

final class AutorizarPerfilTest extends TestCase
{
    private function contexto(bool $logada, ?PerfilUsuario $perfil): ContextoAutenticacao
    {
        return new class ($logada, $perfil) implements ContextoAutenticacao {
            public function __construct(
                private readonly bool $logada,
                private readonly ?PerfilUsuario $perfil,
            ) {
            }

            public function estaLogada(): bool
            {
                return $this->logada;
            }

            public function perfil(): ?PerfilUsuario
            {
                return $this->perfil;
            }
        };
    }

    public function testPerfilPermitidoPassa(): void
    {
        $mw = new AutorizarPerfil($this->contexto(true, PerfilUsuario::Dono), PerfilUsuario::Dono, PerfilUsuario::Recepcao);

        $this->assertNull($mw(new Request()));
    }

    public function testPerfilNegadoRetorna403(): void
    {
        $mw = new AutorizarPerfil($this->contexto(true, PerfilUsuario::Barbeiro), PerfilUsuario::Dono);

        $resposta = $mw(new Request());

        $this->assertInstanceOf(Response::class, $resposta);
    }

    public function testSemPerfilRetorna403(): void
    {
        $mw = new AutorizarPerfil($this->contexto(false, null), PerfilUsuario::Dono);

        $this->assertInstanceOf(Response::class, $mw(new Request()));
    }

    public function testAutenticarRedirecionaQuandoNaoLogada(): void
    {
        $mw = new Autenticar($this->contexto(false, null));

        $this->assertInstanceOf(Response::class, $mw(new Request()));
    }

    public function testAutenticarPassaQuandoLogada(): void
    {
        $mw = new Autenticar($this->contexto(true, PerfilUsuario::Recepcao));

        $this->assertNull($mw(new Request()));
    }
}
