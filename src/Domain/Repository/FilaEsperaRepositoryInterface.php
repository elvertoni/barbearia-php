<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\FilaEspera;

interface FilaEsperaRepositoryInterface
{
    public function salvar(FilaEspera $filaEspera): FilaEspera;

    /**
     * Lista itens da fila de espera para uma data e serviço específicos.
     * Usado ao cancelar um agendamento para verificar se alguém da fila pode ser atendido (Regra 7).
     *
     * @return FilaEspera[]
     */
    public function listarPorDataEServico(string $data, int $servicoId): array;

    /**
     * Lista todos os itens da fila de espera para uma data.
     *
     * @return FilaEspera[]
     */
    public function listarPorData(string $data): array;

    public function remover(int $id): void;
}
