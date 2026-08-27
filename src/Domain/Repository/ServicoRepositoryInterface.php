<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Servico;

interface ServicoRepositoryInterface
{
    public function salvar(Servico $servico): Servico;

    public function buscarPorId(int $id): ?Servico;

    /** @return Servico[] */
    public function listarAtivos(): array;

    /** @return Servico[] */
    public function listarTodos(): array;

    public function atualizar(Servico $servico): void;

    public function excluir(int $id): void;
}
