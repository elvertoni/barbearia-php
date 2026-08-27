<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

/**
 * Lançada quando nenhuma cadeira compatível com o serviço está disponível.
 */
final class CadeiraIncompativelException extends DomainException
{
    public function __construct(int $servicoId)
    {
        parent::__construct(
            sprintf('Nenhuma cadeira compatível com o serviço #%d está cadastrada.', $servicoId)
        );
    }
}
