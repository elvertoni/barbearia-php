<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

/**
 * Lançada ao registrar um usuário com e-mail que já existe.
 */
final class EmailJaCadastradoException extends DomainException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('O e-mail "%s" já está cadastrado.', $email));
    }
}
