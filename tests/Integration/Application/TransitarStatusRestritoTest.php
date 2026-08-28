<?php

declare(strict_types=1);

namespace Tests\Integration\Application;

use App\Application\UseCase\CriarAgendamentoUseCase;
use App\Application\UseCase\TransitarStatusUseCase;
use App\Domain\Entity\Barbeiro;
use App\Domain\Entity\BarbeiroHorario;
use App\Domain\Entity\Cadeira;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\Servico;
use App\Domain\Enum\DiaSemana;
use App\Domain\Enum\StatusAgendamento;
use App\Domain\Exception\AcessoNegadoException;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repository\PdoAgendamentoRepository;
use App\Infrastructure\Repository\PdoBarbeiroHorarioRepository;
use App\Infrastructure\Repository\PdoBarbeiroRepository;
use App\Infrastructure\Repository\PdoCadeiraRepository;
use App\Infrastructure\Repository\PdoClienteRepository;
use App\Infrastructure\Repository\PdoServicoRepository;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * REQUER: MySQL rodando via Docker Compose + migrations aplicadas.
 */
final class TransitarStatusRestritoTest extends TestCase
{
    private PDO $pdo;
    private TransitarStatusUseCase $transitar;
    private int $agendamentoId;
    private int $barbeiroId;

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

        $criar = new CriarAgendamentoUseCase($this->pdo, $agendamentoRepo, $horarioRepo, $cadeiraRepo, $servicoRepo);
        $this->transitar = new TransitarStatusUseCase($agendamentoRepo);

        $this->barbeiroId = $barbeiroRepo->salvar(Barbeiro::criar('João'))->id;
        $clienteId = $clienteRepo->salvar(Cliente::criar('Maria', '11999990001'))->id;
        $servico = $servicoRepo->salvar(Servico::criar('Corte', 30, 5000));
        $cadeira = $cadeiraRepo->salvar(Cadeira::criar('Cadeira 1'));
        $cadeiraRepo->vincularServico($cadeira->id, $servico->id);
        $horarioRepo->salvar(new BarbeiroHorario(
            id: null,
            barbeiroId: $this->barbeiroId,
            diaSemana: DiaSemana::Segunda,
            horaInicio: '08:00',
            horaFim: '18:00',
        ));

        $agendamento = $criar->executar(
            clienteId: $clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $servico->id,
            horaInicio: new DateTimeImmutable('2025-01-06 09:00:00', new DateTimeZone('UTC')),
        );
        $this->agendamentoId = $agendamento->id;
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testBarbeiroDonoDoAgendamentoTransita(): void
    {
        $this->transitar->executar($this->agendamentoId, StatusAgendamento::Confirmado, $this->barbeiroId);

        $this->expectNotToPerformAssertions();
    }

    public function testBarbeiroAlheioRecebeAcessoNegado(): void
    {
        $this->expectException(AcessoNegadoException::class);
        $this->transitar->executar($this->agendamentoId, StatusAgendamento::Confirmado, $this->barbeiroId + 999);
    }

    public function testSemRestricaoTransitaNormalmente(): void
    {
        $this->transitar->executar($this->agendamentoId, StatusAgendamento::Confirmado);

        $this->expectNotToPerformAssertions();
    }
}
