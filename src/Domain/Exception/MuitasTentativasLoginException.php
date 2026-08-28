<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

/**
 * Lançada quando o limite de tentativas de login falhas é atingido.
 */
final class MuitasTentativasLoginException extends DomainException
{
    public function __construct(int $minutos)
    {
        parent::__construct(
            sprintf('Muitas tentativas de login. Tente novamente em %d minuto(s).', $minutos)
        );
    }
}
