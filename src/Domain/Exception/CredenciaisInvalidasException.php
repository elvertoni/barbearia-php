<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

/**
 * Lançada quando e-mail/senha não conferem ou o usuário está inativo.
 * Mensagem genérica de propósito: não revela se o e-mail existe.
 */
final class CredenciaisInvalidasException extends DomainException
{
    public function __construct()
    {
        parent::__construct('E-mail ou senha inválidos.');
    }
}
