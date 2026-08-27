<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Cliente;

interface ClienteRepositoryInterface
{
    public function salvar(Cliente $cliente): Cliente;

    public function buscarPorId(int $id): ?Cliente;

    public function buscarPorTelefone(string $telefone): ?Cliente;

    /** @return Cliente[] */
    public function listarTodos(): array;

    public function atualizar(Cliente $cliente): void;

    public function excluir(int $id): void;
}
