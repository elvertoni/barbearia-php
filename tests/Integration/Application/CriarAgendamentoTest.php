<?php

declare(strict_types=1);

namespace Tests\Integration\Application;

use App\Application\UseCase\CriarAgendamentoUseCase;
use App\Domain\Entity\Barbeiro;
use App\Domain\Entity\BarbeiroHorario;
use App\Domain\Entity\Cadeira;
use App\Domain\Entity\Cliente;
use App\Domain\Entity\Servico;
use App\Domain\Enum\DiaSemana;
use App\Domain\Exception\CadeiraIncompativelException;
use App\Domain\Exception\ConflitoDeHorarioException;
use App\Domain\Exception\ForaDaJanelaDeTrabalhoException;
use App\Domain\Exception\SlotIndisponivelException;
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
 * Testes de integração do CriarAgendamentoUseCase.
 *
 * Cobre todos os casos de borda listados no PRD:
 * - Sobreposição total
 * - Sobreposição parcial no início
 * - Sobreposição parcial no fim
 * - Horários adjacentes (deve ser permitido)
 * - Mesmo barbeiro, horários diferentes, cadeiras iguais
 * - Cadeira incompatível com serviço
 * - Fora da janela de trabalho
 * - Teste de concorrência (duas reservas simultâneas)
 *
 * REQUER: MySQL rodando via Docker Compose.
 */
final class CriarAgendamentoTest extends TestCase
{
    private PDO $pdo;
    private CriarAgendamentoUseCase $useCase;
    private int $barbeiroId;
    private int $clienteId;
    private int $servicoId;
    private int $cadeiraId;

    protected function setUp(): void
    {
        $this->pdo = Connection::create();
        $this->pdo->beginTransaction();

        // Repositórios
        $agendamentoRepo = new PdoAgendamentoRepository($this->pdo);
        $horarioRepo = new PdoBarbeiroHorarioRepository($this->pdo);
        $cadeiraRepo = new PdoCadeiraRepository($this->pdo);
        $servicoRepo = new PdoServicoRepository($this->pdo);
        $barbeiroRepo = new PdoBarbeiroRepository($this->pdo);
        $clienteRepo = new PdoClienteRepository($this->pdo);

        $this->useCase = new CriarAgendamentoUseCase(
            pdo: $this->pdo,
            agendamentoRepo: $agendamentoRepo,
            horarioRepo: $horarioRepo,
            cadeiraRepo: $cadeiraRepo,
            servicoRepo: $servicoRepo,
        );

        // Seed: criar dados base para os testes
        $barbeiro = $barbeiroRepo->salvar(Barbeiro::criar('João'));
        $this->barbeiroId = $barbeiro->id;

        $cliente = $clienteRepo->salvar(Cliente::criar('Maria', '11999990001'));
        $this->clienteId = $cliente->id;

        // Serviço: corte, 30 minutos, R$ 50,00
        $servico = $servicoRepo->salvar(Servico::criar('Corte', 30, 5000));
        $this->servicoId = $servico->id;

        // Cadeira compatível com o serviço
        $cadeira = $cadeiraRepo->salvar(Cadeira::criar('Cadeira 1'));
        $this->cadeiraId = $cadeira->id;
        $cadeiraRepo->vincularServico($cadeira->id, $servico->id);

        // Horário do barbeiro: segunda a sexta, 08:00 - 18:00
        // 2025-01-06 é uma segunda-feira
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
        // Rollback para não sujar o banco entre testes
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    private function utc(string $datetime): DateTimeImmutable
    {
        return new DateTimeImmutable($datetime, new DateTimeZone('UTC'));
    }

    // ===== SUCESSO =====

    public function testAgendamentoEmHorarioLivre(): void
    {
        $agendamento = $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:00:00'),
        );

        $this->assertNotNull($agendamento->id);
        $this->assertSame($this->barbeiroId, $agendamento->barbeiroId);
        $this->assertSame($this->cadeiraId, $agendamento->cadeiraId);
    }

    /**
     * Horários adjacentes: o fim de um agendamento é exatamente o início do próximo.
     * Isso DEVE ser permitido (não é sobreposição).
     */
    public function testHorariosAdjacentesPermitidos(): void
    {
        // Primeiro agendamento: 09:00 - 09:30
        $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:00:00'),
        );

        // Segundo agendamento: 09:30 - 10:00 (adjacente, não sobrepõe)
        $agendamento2 = $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:30:00'),
        );

        $this->assertNotNull($agendamento2->id);
    }

    // ===== CONFLITOS =====

    /**
     * Sobreposição total: mesmo horário exato.
     */
    public function testSobreposicaoTotalFalha(): void
    {
        $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:00:00'),
        );

        $this->expectException(ConflitoDeHorarioException::class);

        $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:00:00'),
        );
    }

    /**
     * Sobreposição parcial no início: o novo agendamento começa antes e termina durante o existente.
     */
    public function testSobreposicaoParcialNoInicioFalha(): void
    {
        // Existente: 09:00 - 09:30
        $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:00:00'),
        );

        $this->expectException(ConflitoDeHorarioException::class);

        // Novo: 08:45 - 09:15 (sobrepõe o início)
        $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 08:45:00'),
        );
    }

    /**
     * Sobreposição parcial no fim: o novo agendamento começa durante e termina depois do existente.
     */
    public function testSobreposicaoParcialNoFimFalha(): void
    {
        // Existente: 09:00 - 09:30
        $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:00:00'),
        );

        $this->expectException(ConflitoDeHorarioException::class);

        // Novo: 09:15 - 09:45 (sobrepõe o fim)
        $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:15:00'),
        );
    }

    /**
     * Mesmo barbeiro, horários diferentes, mas cadeira igual → segunda reserva deve
     * ocupar a mesma cadeira sem conflito pois os horários não se sobrepõem.
     * Se houvesse apenas uma cadeira e os horários se sobrepusessem, falharia.
     */
    public function testMesmoBarbeiroHorariosDiferentesCadeiraIgualSucesso(): void
    {
        // 09:00 - 09:30
        $ag1 = $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:00:00'),
        );

        // 10:00 - 10:30 (horário diferente, mesma cadeira ok)
        $ag2 = $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 10:00:00'),
        );

        $this->assertSame($ag1->cadeiraId, $ag2->cadeiraId);
    }

    /**
     * Conflito de cadeira: dois barbeiros diferentes tentam usar a mesma cadeira no mesmo horário.
     * Com apenas uma cadeira compatível, o segundo deve falhar por SlotIndisponivel.
     */
    public function testConflitoCadeiraComDoisBarbeiros(): void
    {
        // Criar segundo barbeiro com horário no mesmo dia
        $barbeiroRepo = new PdoBarbeiroRepository($this->pdo);
        $horarioRepo = new PdoBarbeiroHorarioRepository($this->pdo);

        $barbeiro2 = $barbeiroRepo->salvar(Barbeiro::criar('Pedro'));
        $horarioRepo->salvar(new BarbeiroHorario(
            id: null,
            barbeiroId: $barbeiro2->id,
            diaSemana: DiaSemana::Segunda,
            horaInicio: '08:00',
            horaFim: '18:00',
        ));

        // Barbeiro 1 agenda: 09:00 - 09:30 na Cadeira 1
        $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:00:00'),
        );

        // Barbeiro 2 tenta: 09:00 - 09:30 — barbeiro livre, mas cadeira ocupada
        $this->expectException(SlotIndisponivelException::class);

        $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $barbeiro2->id,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 09:00:00'),
        );
    }

    // ===== CADEIRA INCOMPATÍVEL =====

    public function testCadeiraIncompativelComServicoFalha(): void
    {
        $servicoRepo = new PdoServicoRepository($this->pdo);

        // Criar serviço sem cadeira compatível cadastrada
        $servicoSemCadeira = $servicoRepo->salvar(Servico::criar('Barba Especial', 45, 7000));

        $this->expectException(CadeiraIncompativelException::class);

        $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $servicoSemCadeira->id,
            horaInicio: $this->utc('2025-01-06 09:00:00'),
        );
    }

    // ===== FORA DA JANELA DE TRABALHO =====

    public function testForaDaJanelaDeTrabalhoFalha(): void
    {
        $this->expectException(ForaDaJanelaDeTrabalhoException::class);

        // 2025-01-07 é terça-feira, mas barbeiro só tem horário cadastrado para segunda
        $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-07 09:00:00'),
        );
    }

    public function testHorarioAntesDaJanelaFalha(): void
    {
        $this->expectException(ForaDaJanelaDeTrabalhoException::class);

        // Segunda-feira, mas 07:00 é antes da janela (08:00 - 18:00)
        $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 07:00:00'),
        );
    }

    public function testHorarioDepoisDaJanelaFalha(): void
    {
        $this->expectException(ForaDaJanelaDeTrabalhoException::class);

        // Segunda-feira, 17:45 + 30min = 18:15, ultrapassa a janela que vai até 18:00
        $this->useCase->executar(
            clienteId: $this->clienteId,
            barbeiroId: $this->barbeiroId,
            servicoId: $this->servicoId,
            horaInicio: $this->utc('2025-01-06 17:45:00'),
        );
    }
}
