<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

/**
 * Lançada ao tentar uma transição de status inválida na máquina de estados.
 */
final class TransicaoInvalidaException extends DomainException
{
    public function __construct(string $de, string $para)
    {
        parent::__construct(
            sprintf('Transição de status inválida: "%s" → "%s" não é permitida.', $de, $para)
        );
    }
}
