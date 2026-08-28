<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface TentativaLoginRepositoryInterface
{
    public function registrar(string $email, string $ip, bool $sucesso): void;

    /**
     * Quantidade de tentativas FALHAS para o e-mail nos últimos $minutos.
     */
    public function contarFalhasRecentes(string $email, int $minutos): int;
}
