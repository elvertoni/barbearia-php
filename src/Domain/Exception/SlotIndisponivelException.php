<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

/**
 * Lançada quando não há nenhum slot disponível (barbeiro OU cadeira ocupados).
 * Indica que o cliente pode ser encaminhado para a fila de espera.
 */
final class SlotIndisponivelException extends DomainException
{
    public function __construct(string $motivo)
    {
        parent::__construct(
            sprintf('Nenhum slot disponível: %s', $motivo)
        );
    }
}
