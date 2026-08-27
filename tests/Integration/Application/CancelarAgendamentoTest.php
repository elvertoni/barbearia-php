<?php

declare(strict_types=1);

namespace Tests\Integration\Application;

use App\Application\UseCase\CancelarAgendamentoUseCase;
use App\Application\UseCase\CriarAgendamentoUseCase;
use App\Application\UseCase\EntrarFilaEsperaUseCase;
use App\Domain\Entity\Barbeiro;
use App\Domain\Entity\BarbeiroHorario;
use App\Domain\Entity\Cadeira;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\Servico;
use App\Domain\Enum\DiaSemana;
use App\Domain\Enum\StatusAgendamento;
use App\Domain\Exception\TransicaoInvalidaException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repository\PdoAgendamentoRepository;
use App\Infrastructure\Repository\PdoBarbeiroHorarioRepository;
use App\Infrastructure\Repository\PdoBarbeiroRepository;
use App\Infrastructure\Repository\PdoCadeiraRepository;
use App\Infrastructure\Repository\PdoClienteRepository;
use App\Infrastructure\Repository\PdoFilaEsperaRepository;
use App\Infrastructure\Repository\PdoServicoRepository;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Testes de integração do CancelarAgendamentoUseCase.
 *
 * Valida:
 * - Transição de estado para cancelado
 * - Fila de espera é sinalizada ao cancelar
 * - Transições inválidas lançam exceção
 */
final class CancelarAgendamentoTest extends TestCase
{
    private PDO $pdo;
    private CancelarAgendamentoUseCase $cancelarUseCase;
    private CriarAgendamentoUseCase $criarUseCase;
    private EntrarFilaEsperaUseCase $filaUseCase;
    private int $barbeiroId;
    private int $clienteId;
    private int $servicoId;

    protected function setUp(): void
    {
        $this->pdo = Connection::create();
        $this->pdo->beginTransaction();

        $agendamentoRepo = new PdoAgendamentoRepository($this->pdo);
        $horarioRepo = new PdoBarbeiroHorarioRepository($this->pdo);
        $cadeiraRepo = new PdoCadeiraRepository($this->pdo);
        $servicoRepo = new PdoServicoRepository($this->pdo);
        $barbeiroRepo = new PdoBarbeiroRepository($this->pdo);
        $clienteRepo = new PdoClienteRepository($this->pdo);
        $filaEsperaRepo = new PdoFilaEsperaRepository($this->pdo);

        $this->criarUseCase = new CriarAgendamentoUseCase(
            pdo: $this->pdo,
            agendamentoRepo: $agendamentoRepo,
            horarioRepo: $horarioRepo,
            cadeiraRepo: $cadeiraRepo,
            servicoRepo: $servicoRepo,
        );

        $this->cancelarUseCase = new CancelarAgendamentoUseCase(
            pdo: $this->pdo,
            agendamentoRepo: $agendamentoRepo,
            filaEsperaRepo: $filaEsperaRepo,
        );

        $this->filaUseCase = new EntrarFilaEsperaUseCase(
            filaEsperaRepo: $filaEsperaRepo,
        );

        // Seed
        $barbeiro = $barbeiroRepo->salvar(Barbeiro::criar('João'));
        $this->barbeiroId = $barbeiro->id;

        $cliente = $clienteRepo->salvar(Cliente::criar('Maria', '11999990002'));
        $this->clienteId = $cliente->id;

        $servico = $servicoRepo->salvar(Servico::criar('Corte', 30, 5000));
        $this->servicoId = $servico->id;

        $cadeira = $cadeiraRepo->salvar(Cadeira::criar('Cadeira 1'));
        $cadeiraRepo->vincularServico($cadeira->id, $servico->id);

        $horarioRepo->salvar(new BarbeiroHorario(
            id: null,
            barbeiroId: $this->barbeiroId,
            diaSemana: DiaSemana::Segunda,
            horaInicio: '08:00',
            horaFim: '18:00',
        ));
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function utc(string $datetime): DateTimeImmutable
    {
        return new DateTimeImmutable($datetime, new DateTimeZone('UTC'));
    }

    public function testCancelarAgendamentoSolicitado(): void
    {
        $agendamento = $this->criarUseCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:00:00'),
        );

        $resultado = $this->cancelarUseCase->executar($agendamento->id);

        $this->assertSame(StatusAgendamento::Cancelado, $resultado['agendamento']->status());
    }

    public function testCancelamentoSinalizaFilaDeEspera(): void
    {
        $agendamento = $this->criarUseCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:00:00'),
        );

        // Cliente entra na fila de espera para o mesmo dia/serviço
        $this->filaUseCase->executar(
            clienteId: $this->clienteId,
            servicoId: $this->servicoId,
            dataDesejada: '2025-01-06',
        );

        // Cancelar o agendamento deve sinalizar a fila
        $resultado = $this->cancelarUseCase->executar($agendamento->id);

        $this->assertNotEmpty($resultado['fila_sinalizada']);
        $this->assertCount(1, $resultado['fila_sinalizada']);
    }

    public function testCancelarAgendamentoConcluidoFalha(): void
    {
        $agendamento = $this->criarUseCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:00:00'),
        );

        // Simular fluxo completo: solicitado → confirmado → em_atendimento → concluido
        $this->pdo->prepare('UPDATE agendamentos SET status = :status WHERE id = :id')
            ->execute(['status' => 'concluido', 'id' => $agendamento->id]);

        $this->expectException(TransicaoInvalidaException::class);

        $this->cancelarUseCase->executar($agendamento->id);
    }
}
