<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\BarbeiroHorario;
use App\Domain\Enum\DiaSemana;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários da entidade BarbeiroHorario (Regra 3 do PRD).
 */
final class BarbeiroHorarioTest extends TestCase
{
    private function criarHorario(string $inicio = '08:00', string $fim = '18:00'): BarbeiroHorario
    {
        return new BarbeiroHorario(
            id: 1,
            barbeiroId: 1,
            diaSemana: DiaSemana::Segunda,
            horaInicio: $inicio,
            horaFim: $fim,
        );
    }

    public function testIntervaloDentroDeJanela(): void
    {
        $horario = $this->criarHorario('08:00', '18:00');
        $this->assertTrue($horario->contemIntervalo('09:00', '10:00'));
    }

    public function testIntervaloExatamenteIgualAJanela(): void
    {
        $horario = $this->criarHorario('08:00', '18:00');
        $this->assertTrue($horario->contemIntervalo('08:00', '18:00'));
    }

    public function testIntervaloComecandoAntesDaJanela(): void
    {
        $horario = $this->criarHorario('08:00', '18:00');
        $this->assertFalse($horario->contemIntervalo('07:00', '09:00'));
    }

    public function testIntervaloTerminandoDepoisDaJanela(): void
    {
        $horario = $this->criarHorario('08:00', '18:00');
        $this->assertFalse($horario->contemIntervalo('17:00', '19:00'));
    }

    public function testIntervaloTotalmenteForaDaJanela(): void
    {
        $horario = $this->criarHorario('08:00', '18:00');
        $this->assertFalse($horario->contemIntervalo('19:00', '20:00'));
    }
}
