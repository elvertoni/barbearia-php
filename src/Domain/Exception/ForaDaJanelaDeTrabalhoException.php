<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

/**
 * Lançada quando o horário solicitado está fora da janela de trabalho do barbeiro.
 */
final class ForaDaJanelaDeTrabalhoException extends DomainException
{
    public function __construct(int $barbeiroId, string $diaSemana)
    {
        parent::__construct(
            sprintf(
                'Barbeiro #%d não trabalha no horário solicitado (%s).',
                $barbeiroId,
                $diaSemana
            )
        );
    }
}
