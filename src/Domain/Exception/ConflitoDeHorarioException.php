<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

/**
 * Lançada quando o horário solicitado conflita com um agendamento existente
 * (barbeiro ou cadeira já ocupados no intervalo).
 */
final class ConflitoDeHorarioException extends DomainException
{
    public function __construct(string $recurso, int $recursoId)
    {
        parent::__construct(
            sprintf('Conflito de horário: %s #%d já está ocupado(a) no intervalo solicitado.', $recurso, $recursoId)
        );
    }
}
