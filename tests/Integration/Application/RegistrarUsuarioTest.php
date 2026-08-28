<?php

declare(strict_types=1);

namespace Tests\Integration\Application;

use App\Application\UseCase\RegistrarUsuarioUseCase;
use App\Domain\Enum\PerfilUsuario;
use App\Domain\Exception\EmailJaCadastradoException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repository\PdoUsuarioRepository;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * REQUER: MySQL rodando via Docker Compose + migrations aplicadas.
 */
final class RegistrarUsuarioTest extends TestCase
{
    private PDO $pdo;
    private RegistrarUsuarioUseCase $useCase;
    private PdoUsuarioRepository $repo;

    protected function setUp(): void
    {
        $this->pdo = Connection::create();
        $this->pdo->beginTransaction();

        $this->repo = new PdoUsuarioRepository($this->pdo);
        $this->useCase = new RegistrarUsuarioUseCase($this->repo);
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testRegistraECalculaHash(): void
    {
        $usuario = $this->useCase->executar('João', 'joao@teste.local', 'senhaForte1', PerfilUsuario::Recepcao);

        $this->assertNotNull($usuario->id);
        $this->assertSame('joao@teste.local', $usuario->email);
        $this->assertTrue($usuario->verificarSenha('senhaForte1'));

        $persistido = $this->repo->buscarPorEmail('joao@teste.local');
        $this->assertNotNull($persistido);
        $this->assertTrue($persistido->verificarSenha('senhaForte1'));
    }

    public function testEmailNormalizadoParaMinusculas(): void
    {
        $usuario = $this->useCase->executar('João', 'JOAO@Teste.Local', 'senhaForte1', PerfilUsuario::Dono);

        $this->assertSame('joao@teste.local', $usuario->email);
    }

    public function testEmailDuplicadoFalha(): void
    {
        $this->useCase->executar('João', 'dup@teste.local', 'senhaForte1', PerfilUsuario::Dono);

        $this->expectException(EmailJaCadastradoException::class);
        $this->useCase->executar('Outro', 'dup@teste.local', 'outraSenha1', PerfilUsuario::Recepcao);
    }
}
