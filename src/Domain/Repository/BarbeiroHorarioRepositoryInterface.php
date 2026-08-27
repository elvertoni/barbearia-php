<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\BarbeiroHorario;
use App\Domain\Enum\DiaSemana;

interface BarbeiroHorarioRepositoryInterface
{
    public function salvar(BarbeiroHorario $horario): BarbeiroHorario;

    /**
     * Busca o horário de trabalho de um barbeiro em um dia da semana específico.
     * Retorna null se o barbeiro não trabalha nesse dia (Regra 3).
     */
    public function buscarPorBarbeiroEDia(int $barbeiroId, DiaSemana $diaSemana): ?BarbeiroHorario;

    /**
     * Lista todos os horários de trabalho de um barbeiro.
     *
     * @return BarbeiroHorario[]
     */
    public function listarPorBarbeiro(int $barbeiroId): array;

    public function excluir(int $id): void;

    public function excluirPorBarbeiro(int $barbeiroId): void;
}
