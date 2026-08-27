<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\DiaSemana;

/**
 * Horário de trabalho de um barbeiro em um dia da semana (Regra 3 do PRD).
 */
final class BarbeiroHorario
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $barbeiroId,
        public readonly DiaSemana $diaSemana,
        public readonly string $horaInicio,
        public readonly string $horaFim,
    ) {
    }

    /**
     * Verifica se um intervalo de horário está dentro desta janela de trabalho.
     *
     * @param string $inicio Hora de início no formato H:i (ex: "09:00")
     * @param string $fim Hora de fim no formato H:i (ex: "10:00")
     */
    public function contemIntervalo(string $inicio, string $fim): bool
    {
        return $inicio >= $this->horaInicio && $fim <= $this->horaFim;
    }
}
