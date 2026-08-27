<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Barbeiro;

interface BarbeiroRepositoryInterface
{
    public function salvar(Barbeiro $barbeiro): Barbeiro;

    public function buscarPorId(int $id): ?Barbeiro;

    /** @return Barbeiro[] */
    public function listarAtivos(): array;

    /** @return Barbeiro[] */
    public function listarTodos(): array;

    public function atualizar(Barbeiro $barbeiro): void;

    public function excluir(int $id): void;
}
