<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Usuario;

interface UsuarioRepositoryInterface
{
    public function salvar(Usuario $usuario): Usuario;

    public function buscarPorId(int $id): ?Usuario;

    public function buscarPorEmail(string $email): ?Usuario;

    /** @return Usuario[] */
    public function listarTodos(): array;

    public function atualizar(Usuario $usuario): void;

    public function excluir(int $id): void;

    public function contarTodos(): int;
}
