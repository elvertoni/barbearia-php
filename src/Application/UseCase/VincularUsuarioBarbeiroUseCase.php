<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Enum\PerfilUsuario;
use App\Domain\Repository\BarbeiroRepositoryInterface;
use App\Domain\Repository\UsuarioRepositoryInterface;

/**
 * Caso de uso: Vincular Usuario a Barbeiro.
 *
 * Liga um usuário de perfil `barbeiro` ao recurso agendável `barbeiros`,
 * de modo que ele enxergue e opere a própria agenda.
 */
final class VincularUsuarioBarbeiroUseCase
{
    public function __construct(
        private readonly BarbeiroRepositoryInterface $barbeiroRepo,
        private readonly UsuarioRepositoryInterface $usuarioRepo,
    ) {
    }

    public function executar(int $barbeiroId, int $usuarioId): void
    {
        $barbeiro = $this->barbeiroRepo->buscarPorId($barbeiroId);

        if ($barbeiro === null) {
            throw new \InvalidArgumentException("Barbeiro #{$barbeiroId} não encontrado.");
        }

        $usuario = $this->usuarioRepo->buscarPorId($usuarioId);

        if ($usuario === null) {
            throw new \InvalidArgumentException("Usuário #{$usuarioId} não encontrado.");
        }

        if ($usuario->perfil !== PerfilUsuario::Barbeiro) {
            throw new \InvalidArgumentException('Só é possível vincular usuários de perfil barbeiro.');
        }

        $this->barbeiroRepo->atualizar($barbeiro->comUsuario($usuarioId));
    }
}
