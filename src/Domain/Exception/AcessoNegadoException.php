<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

/**
 * Lançada quando um usuário tenta operar um recurso que não lhe pertence
 * (ex.: barbeiro transitando o status de um agendamento de outro barbeiro).
 */
final class AcessoNegadoException extends DomainException
{
    public function __construct(string $motivo)
    {
        parent::__construct(sprintf('Acesso negado: %s', $motivo));
    }
}
