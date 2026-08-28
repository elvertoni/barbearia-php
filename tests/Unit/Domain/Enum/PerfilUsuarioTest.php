<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Enum;

use App\Domain\Enum\PerfilUsuario;
use PHPUnit\Framework\TestCase;

final class PerfilUsuarioTest extends TestCase
{
    public function testTodosOsPerfisTemLabel(): void
    {
        foreach (PerfilUsuario::cases() as $perfil) {
            $this->assertNotEmpty($perfil->label());
        }
    }

    public function testAcessaPainelParaEquipeInterna(): void
    {
        $this->assertTrue(PerfilUsuario::Dono->acessaPainel());
        $this->assertTrue(PerfilUsuario::Recepcao->acessaPainel());
        $this->assertTrue(PerfilUsuario::Barbeiro->acessaPainel());
    }

    public function testClienteNaoAcessaPainel(): void
    {
        $this->assertFalse(PerfilUsuario::Cliente->acessaPainel());
    }

    public function testValoresBatemComAColunaEnum(): void
    {
        $this->assertSame('dono', PerfilUsuario::Dono->value);
        $this->assertSame('recepcao', PerfilUsuario::Recepcao->value);
        $this->assertSame('barbeiro', PerfilUsuario::Barbeiro->value);
        $this->assertSame('cliente', PerfilUsuario::Cliente->value);
    }
}
