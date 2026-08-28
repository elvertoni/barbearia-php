<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Usuario;
use App\Domain\Enum\PerfilUsuario;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class UsuarioTest extends TestCase
{
    public function testCriarNaoTemIdEComecaAtivo(): void
    {
        $hash = password_hash('segredo123', PASSWORD_DEFAULT);
        $usuario = Usuario::criar('Ana', 'ana@x.com', $hash, PerfilUsuario::Recepcao);

        $this->assertNull($usuario->id);
        $this->assertTrue($usuario->ativo);
        $this->assertSame(PerfilUsuario::Recepcao, $usuario->perfil);
    }

    public function testComIdPreservaOsDemaisCampos(): void
    {
        $hash = password_hash('segredo123', PASSWORD_DEFAULT);
        $usuario = Usuario::criar('Ana', 'ana@x.com', $hash, PerfilUsuario::Dono)->comId(7);

        $this->assertSame(7, $usuario->id);
        $this->assertSame('ana@x.com', $usuario->email);
        $this->assertSame($hash, $usuario->senhaHash);
    }

    public function testVerificarSenhaConfereContraOHash(): void
    {
        $usuario = Usuario::reconstituir(
            id: 1,
            nome: 'Ana',
            email: 'ana@x.com',
            senhaHash: password_hash('segredo123', PASSWORD_DEFAULT),
            perfil: PerfilUsuario::Barbeiro,
            ativo: true,
            criadoEm: new DateTimeImmutable('now', new DateTimeZone('UTC')),
        );

        $this->assertTrue($usuario->verificarSenha('segredo123'));
        $this->assertFalse($usuario->verificarSenha('errada'));
    }

    public function testSenhaNuncaEArmazenadaEmTextoPuro(): void
    {
        $usuario = Usuario::criar('Ana', 'ana@x.com', password_hash('segredo123', PASSWORD_DEFAULT), PerfilUsuario::Dono);

        $this->assertNotSame('segredo123', $usuario->senhaHash);
        $this->assertStringStartsWith('$', $usuario->senhaHash);
    }
}
