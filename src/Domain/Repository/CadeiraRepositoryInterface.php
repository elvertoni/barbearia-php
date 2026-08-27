<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Cadeira;

interface CadeiraRepositoryInterface
{
    public function salvar(Cadeira $cadeira): Cadeira;

    public function buscarPorId(int $id): ?Cadeira;

    /** @return Cadeira[] */
    public function listarAtivas(): array;

    /** @return Cadeira[] */
    public function listarTodas(): array;

    /**
     * Retorna as cadeiras compatíveis com um serviço específico (Regra 2).
     *
     * @return Cadeira[]
     */
    public function listarCompativeisComServico(int $servicoId): array;

    public function atualizar(Cadeira $cadeira): void;

    public function excluir(int $id): void;

    /**
     * Vincula uma cadeira a um serviço na tabela associativa.
     */
    public function vincularServico(int $cadeiraId, int $servicoId): void;

    /**
     * Remove o vínculo de uma cadeira com um serviço.
     */
    public function desvincularServico(int $cadeiraId, int $servicoId): void;
}
