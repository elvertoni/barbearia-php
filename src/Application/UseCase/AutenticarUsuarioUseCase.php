<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Domain\Entity\Usuario;
use App\Domain\Exception\CredenciaisInvalidasException;
use App\Domain\Exception\MuitasTentativasLoginException;
use App\Domain\Repository\TentativaLoginRepositoryInterface;
use App\Domain\Repository\UsuarioRepositoryInterface;

/**
 * Caso de uso: Autenticar Usuario.
 *
 * Valida credenciais, aplica rate limiting por e-mail e registra cada tentativa.
 */
final class AutenticarUsuarioUseCase
{
    private const MAX_FALHAS = 5;
    private const JANELA_MINUTOS = 15;

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarioRepo,
        private readonly TentativaLoginRepositoryInterface $tentativaRepo,
    ) {
    }

    /**
     * @throws MuitasTentativasLoginException
     * @throws CredenciaisInvalidasException
     */
    public function executar(string $email, string $senhaPlana, string $ip): Usuario
    {
        $email = mb_strtolower(trim($email));

        if ($this->tentativaRepo->contarFalhasRecentes($email, self::JANELA_MINUTOS) >= self::MAX_FALHAS) {
            throw new MuitasTentativasLoginException(self::JANELA_MINUTOS);
        }

        $usuario = $this->usuarioRepo->buscarPorEmail($email);

        if ($usuario === null || !$usuario->ativo || !$usuario->verificarSenha($senhaPlana)) {
            $this->tentativaRepo->registrar($email, $ip, false);
            throw new CredenciaisInvalidasException();
        }

        $this->tentativaRepo->registrar($email, $ip, true);

        return $usuario;
    }
}
