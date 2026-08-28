<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Usuario;
use App\Domain\Enum\PerfilUsuario;
use App\Domain\Exception\EmailJaCadastradoException;
use App\Domain\Repository\UsuarioRepositoryInterface;

/**
 * Caso de uso: Registrar Usuario.
 *
 * Calcula o hash da senha e persiste. Rejeita e-mail já cadastrado.
 */
final class RegistrarUsuarioUseCase
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarioRepo,
    ) {
    }

    /**
     * @throws EmailJaCadastradoException
     */
    public function executar(string $nome, string $email, string $senhaPlana, PerfilUsuario $perfil): Usuario
    {
        $email = mb_strtolower(trim($email));

        if ($this->usuarioRepo->buscarPorEmail($email) !== null) {
            throw new EmailJaCadastradoException($email);
        }

        $usuario = Usuario::criar(
            nome: trim($nome),
            email: $email,
            senhaHash: password_hash($senhaPlana, PASSWORD_DEFAULT),
            perfil: $perfil,
        );

        return $this->usuarioRepo->salvar($usuario);
    }
}
