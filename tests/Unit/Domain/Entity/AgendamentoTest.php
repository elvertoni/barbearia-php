<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Agendamento;
use App\Domain\Enum\StatusAgendamento;
use App\Domain\Exception\TransicaoInvalidaException;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

/**
 * Testes unitários da máquina de estados do Agendamento (Regra 6 do PRD).
 *
 * Cobre todas as transições válidas e inválidas.
 */
final class AgendamentoTest extends TestCase
{
    private function criarAgendamento(StatusAgendamento $status = StatusAgendamento::Solicitado): Agendamento
    {
        $tz = new DateTimeZone('UTC');
        $inicio = new DateTimeImmutable('2025-01-06 09:00:00', $tz);
        $fim = new DateTimeImmutable('2025-01-06 09:30:00', $tz);

        return Agendamento::reconstituir(
            id: 1,
            clienteId: 1,
            barbeiroId: 1,
            cadeiraId: 1,
            servicoId: 1,
            horaInicio: $inicio,
            horaFim: $fim,
            status: $status,
            criadoEm: new DateTimeImmutable('now', $tz),
        );
    }

    // ===== Transições VÁLIDAS =====

    public function testSolicitadoParaConfirmado(): void
    {
        $agendamento = $this->criarAgendamento(StatusAgendamento::Solicitado);
        $agendamento->transitar(StatusAgendamento::Confirmado);
        $this->assertSame(StatusAgendamento::Confirmado, $agendamento->status());
    }

    public function testSolicitadoParaCancelado(): void
    {
        $agendamento = $this->criarAgendamento(StatusAgendamento::Solicitado);
        $agendamento->transitar(StatusAgendamento::Cancelado);
        $this->assertSame(StatusAgendamento::Cancelado, $agendamento->status());
    }

    public function testConfirmadoParaEmAtendimento(): void
    {
        $agendamento = $this->criarAgendamento(StatusAgendamento::Confirmado);
        $agendamento->transitar(StatusAgendamento::EmAtendimento);
        $this->assertSame(StatusAgendamento::EmAtendimento, $agendamento->status());
    }

    public function testConfirmadoParaCancelado(): void
    {
        $agendamento = $this->criarAgendamento(StatusAgendamento::Confirmado);
        $agendamento->transitar(StatusAgendamento::Cancelado);
        $this->assertSame(StatusAgendamento::Cancelado, $agendamento->status());
    }

    public function testConfirmadoParaNoShow(): void
    {
        $agendamento = $this->criarAgendamento(StatusAgendamento::Confirmado);
        $agendamento->transitar(StatusAgendamento::NoShow);
        $this->assertSame(StatusAgendamento::NoShow, $agendamento->status());
    }

    public function testEmAtendimentoParaConcluido(): void
    {
        $agendamento = $this->criarAgendamento(StatusAgendamento::EmAtendimento);
        $agendamento->transitar(StatusAgendamento::Concluido);
        $this->assertSame(StatusAgendamento::Concluido, $agendamento->status());
    }

    // ===== Transições INVÁLIDAS =====

    public function testSolicitadoNaoPodeIrParaEmAtendimento(): void
    {
        $this->expectException(TransicaoInvalidaException::class);
        $agendamento = $this->criarAgendamento(StatusAgendamento::Solicitado);
        $agendamento->transitar(StatusAgendamento::EmAtendimento);
    }

    public function testSolicitadoNaoPodeIrParaConcluido(): void
    {
        $this->expectException(TransicaoInvalidaException::class);
        $agendamento = $this->criarAgendamento(StatusAgendamento::Solicitado);
        $agendamento->transitar(StatusAgendamento::Concluido);
    }

    public function testSolicitadoNaoPodeIrParaNoShow(): void
    {
        $this->expectException(TransicaoInvalidaException::class);
        $agendamento = $this->criarAgendamento(StatusAgendamento::Solicitado);
        $agendamento->transitar(StatusAgendamento::NoShow);
    }

    public function testConfirmadoNaoPodeIrParaConcluido(): void
    {
        $this->expectException(TransicaoInvalidaException::class);
        $agendamento = $this->criarAgendamento(StatusAgendamento::Confirmado);
        $agendamento->transitar(StatusAgendamento::Concluido);
    }

    public function testEmAtendimentoNaoPodeIrParaCancelado(): void
    {
        $this->expectException(TransicaoInvalidaException::class);
        $agendamento = $this->criarAgendamento(StatusAgendamento::EmAtendimento);
        $agendamento->transitar(StatusAgendamento::Cancelado);
    }

    public function testConcluidoNaoPodeTransitarParaNenhumStatus(): void
    {
        $agendamento = $this->criarAgendamento(StatusAgendamento::Concluido);

        foreach (StatusAgendamento::cases() as $status) {
            try {
                $agendamento->transitar($status);
                $this->fail("Transição de concluido para {$status->value} deveria ter falhado.");
            } catch (TransicaoInvalidaException) {
                // Esperado
            }
        }

        $this->assertSame(StatusAgendamento::Concluido, $agendamento->status());
    }

    public function testCanceladoNaoPodeTransitarParaNenhumStatus(): void
    {
        $agendamento = $this->criarAgendamento(StatusAgendamento::Cancelado);

        foreach (StatusAgendamento::cases() as $status) {
            try {
                $agendamento->transitar($status);
                $this->fail("Transição de cancelado para {$status->value} deveria ter falhado.");
            } catch (TransicaoInvalidaException) {
                // Esperado
            }
        }

        $this->assertSame(StatusAgendamento::Cancelado, $agendamento->status());
    }

    public function testNoShowNaoPodeTransitarParaNenhumStatus(): void
    {
        $agendamento = $this->criarAgendamento(StatusAgendamento::NoShow);

        foreach (StatusAgendamento::cases() as $status) {
            try {
                $agendamento->transitar($status);
                $this->fail("Transição de no_show para {$status->value} deveria ter falhado.");
            } catch (TransicaoInvalidaException) {
                // Esperado
            }
        }

        $this->assertSame(StatusAgendamento::NoShow, $agendamento->status());
    }

    // ===== Status inicial =====

    public function testNovoAgendamentoComecaComoSolicitado(): void
    {
        $tz = new DateTimeZone('UTC');
        $agendamento = Agendamento::criar(
            clienteId: 1,
            barbeiroId: 1,
            cadeiraId: 1,
            servicoId: 1,
            horaInicio: new DateTimeImmutable('2025-01-06 09:00:00', $tz),
            horaFim: new DateTimeImmutable('2025-01-06 09:30:00', $tz),
        );

        $this->assertSame(StatusAgendamento::Solicitado, $agendamento->status());
    }

    // ===== Fluxo completo =====

    public function testFluxoCompletoSolicitadoAteConcluido(): void
    {
        $agendamento = $this->criarAgendamento(StatusAgendamento::Solicitado);

        $agendamento->transitar(StatusAgendamento::Confirmado);
        $this->assertSame(StatusAgendamento::Confirmado, $agendamento->status());

        $agendamento->transitar(StatusAgendamento::EmAtendimento);
        $this->assertSame(StatusAgendamento::EmAtendimento, $agendamento->status());

        $agendamento->transitar(StatusAgendamento::Concluido);
        $this->assertSame(StatusAgendamento::Concluido, $agendamento->status());
    }

    // ===== OcupaSlot =====

    public function testStatusQueOcupamSlot(): void
    {
        $this->assertTrue(StatusAgendamento::Solicitado->ocupaSlot());
        $this->assertTrue(StatusAgendamento::Confirmado->ocupaSlot());
        $this->assertTrue(StatusAgendamento::EmAtendimento->ocupaSlot());
    }

    public function testStatusQueNaoOcupamSlot(): void
    {
        $this->assertFalse(StatusAgendamento::Concluido->ocupaSlot());
        $this->assertFalse(StatusAgendamento::Cancelado->ocupaSlot());
        $this->assertFalse(StatusAgendamento::NoShow->ocupaSlot());
    }
}
