<?php

declare(strict_types=1);

namespace Tests\Integration\Application;

use App\Application\UseCase\AutenticarUsuarioUseCase;
use App\Application\UseCase\RegistrarUsuarioUseCase;
use App\Domain\Enum\PerfilUsuario;
use App\Domain\Exception\CredenciaisInvalidasException;
use App\Domain\Exception\MuitasTentativasLoginException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repository\PdoTentativaLoginRepository;
use App\Infrastructure\Repository\PdoUsuarioRepository;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * REQUER: MySQL rodando via Docker Compose + migrations aplicadas.
 */
final class AutenticarUsuarioTest extends TestCase
{
    private PDO $pdo;
    private AutenticarUsuarioUseCase $useCase;
    private PdoUsuarioRepository $usuarioRepo;

    protected function setUp(): void
    {
        $this->pdo = Connection::create();
        $this->pdo->beginTransaction();

        $this->usuarioRepo = new PdoUsuarioRepository($this->pdo);
        $tentativaRepo = new PdoTentativaLoginRepository($this->pdo);
        $this->useCase = new AutenticarUsuarioUseCase($this->usuarioRepo, $tentativaRepo);

        (new RegistrarUsuarioUseCase($this->usuarioRepo))
            ->executar('Ana', 'ana@teste.local', 'senhaForte1', PerfilUsuario::Recepcao);
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testLoginComCredenciaisCorretas(): void
    {
        $usuario = $this->useCase->executar('ana@teste.local', 'senhaForte1', '127.0.0.1');

        $this->assertSame('ana@teste.local', $usuario->email);
        $this->assertSame(PerfilUsuario::Recepcao, $usuario->perfil);
    }

    public function testSenhaErradaFalha(): void
    {
        $this->expectException(CredenciaisInvalidasException::class);
        $this->useCase->executar('ana@teste.local', 'errada', '127.0.0.1');
    }

    public function testEmailInexistenteFalha(): void
    {
        $this->expectException(CredenciaisInvalidasException::class);
        $this->useCase->executar('ninguem@teste.local', 'qualquer', '127.0.0.1');
    }

    public function testUsuarioInativoFalha(): void
    {
        $ana = $this->usuarioRepo->buscarPorEmail('ana@teste.local');
        $inativo = \App\Domain\Entity\Usuario::reconstituir(
            id: $ana->id,
            nome: $ana->nome,
            email: $ana->email,
            senhaHash: $ana->senhaHash,
            perfil: $ana->perfil,
            ativo: false,
            criadoEm: $ana->criadoEm,
        );
        $this->usuarioRepo->atualizar($inativo);

        $this->expectException(CredenciaisInvalidasException::class);
        $this->useCase->executar('ana@teste.local', 'senhaForte1', '127.0.0.1');
    }

    public function testBloqueiaAposCincoFalhas(): void
    {
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->useCase->executar('ana@teste.local', 'errada', '127.0.0.1');
            } catch (CredenciaisInvalidasException) {
                // esperado
            }
        }

        $this->expectException(MuitasTentativasLoginException::class);
        $this->useCase->executar('ana@teste.local', 'senhaForte1', '127.0.0.1');
    }
}
