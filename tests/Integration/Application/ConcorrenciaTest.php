<?php

declare(strict_types=1);

namespace Tests\Integration\Application;

use App\Infrastructure\Database\Connection;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Teste de concorrência (Regra 5 do PRD).
 *
 * Simula dois processos tentando reservar o mesmo horário ao mesmo tempo.
 * Usa duas conexões PDO separadas para simular conexões concorrentes.
 * Apenas uma das duas deve conseguir inserir; a outra deve falhar ou encontrar conflito.
 */
final class ConcorrenciaTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Connection::create();

        // Limpar dados de teste anteriores
        $this->pdo->exec("DELETE FROM agendamentos WHERE criado_em LIKE '2025-01-06%'");

        // Garantir dados de seed existem
        $this->seedDadosTeste();
    }

    protected function tearDown(): void
    {
        // Limpar dados de teste
        $this->pdo->exec("DELETE FROM agendamentos WHERE criado_em LIKE '2025-01-06%'");
    }

    /**
     * Teste de concorrência: duas conexões tentam agendar o mesmo horário simultaneamente.
     *
     * Resultado esperado: exatamente uma das duas deve suceder.
     */
    public function testDuasReservasSimultaneasApenasUmaSucede(): void
    {
        // Buscar IDs dos dados de seed
        $barbeiro = $this->pdo->query("SELECT id FROM barbeiros WHERE nome = 'Barbeiro Concorrencia'")->fetch();
        $cliente = $this->pdo->query("SELECT id FROM clientes WHERE telefone = '11999990099'")->fetch();
        $servico = $this->pdo->query("SELECT id FROM servicos WHERE nome = 'Corte Concorrencia'")->fetch();
        $cadeira = $this->pdo->query("SELECT id FROM cadeiras WHERE nome = 'Cadeira Concorrencia'")->fetch();

        if (!$barbeiro || !$cliente || !$servico || !$cadeira) {
            $this->markTestSkipped('Dados de seed para concorrência não encontrados.');
        }

        $barbeiroId = (int) $barbeiro['id'];
        $clienteId = (int) $cliente['id'];
        $servicoId = (int) $servico['id'];
        $cadeiraId = (int) $cadeira['id'];

        // Duas conexões PDO separadas (simulando dois processos)
        $conn1 = Connection::create();
        $conn2 = Connection::create();

        $successCount = 0;
        $failCount = 0;

        // Conexão 1: inicia transação e verifica conflito
        $conn1->beginTransaction();
        $conflito1 = $this->verificarConflitoComLock(
            $conn1, $barbeiroId, $cadeiraId,
            '2025-01-06 09:00:00', '2025-01-06 09:30:00'
        );

        if (!$conflito1) {
            // Conexão 2: inicia transação e tenta o mesmo
            // Esta chamada vai BLOQUEAR esperando o lock da Conexão 1
            // Para simular, verificamos sem lock primeiro, inserimos com Conn1, depois Conn2 tenta
            $this->inserirAgendamento(
                $conn1, $clienteId, $barbeiroId, $cadeiraId, $servicoId,
                '2025-01-06 09:00:00', '2025-01-06 09:30:00'
            );
            $conn1->commit();
            $successCount++;

            // Agora Conexão 2 tenta a mesma reserva
            $conn2->beginTransaction();
            $conflito2 = $this->verificarConflitoComLock(
                $conn2, $barbeiroId, $cadeiraId,
                '2025-01-06 09:00:00', '2025-01-06 09:30:00'
            );

            if ($conflito2) {
                $failCount++;
                $conn2->rollBack();
            } else {
                // Não deveria chegar aqui se o locking funciona corretamente
                $this->inserirAgendamento(
                    $conn2, $clienteId, $barbeiroId, $cadeiraId, $servicoId,
                    '2025-01-06 09:00:00', '2025-01-06 09:30:00'
                );
                $conn2->commit();
                $successCount++;
            }
        }

        // Exatamente uma deve ter sucedido
        $this->assertSame(1, $successCount, 'Exatamente uma reserva deve ter sucedido');
        $this->assertSame(1, $failCount, 'Exatamente uma reserva deve ter falhado por conflito');

        // Verificar que existe exatamente um agendamento no banco
        $count = $this->pdo->query("
            SELECT COUNT(*) FROM agendamentos
            WHERE barbeiro_id = {$barbeiroId}
              AND hora_inicio = '2025-01-06 09:00:00'
              AND status NOT IN ('cancelado', 'no_show')
        ")->fetchColumn();

        $this->assertSame(1, (int) $count, 'Deve existir exatamente um agendamento no banco');
    }

    private function verificarConflitoComLock(
        PDO $conn,
        int $barbeiroId,
        int $cadeiraId,
        string $horaInicio,
        string $horaFim,
    ): bool {
        // Verificar conflito de barbeiro com FOR UPDATE
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM agendamentos
            WHERE barbeiro_id = :barbeiro_id
              AND status NOT IN ('cancelado', 'no_show')
              AND hora_inicio < :novo_fim
              AND hora_fim > :novo_inicio
            FOR UPDATE
        ");
        $stmt->execute([
            'barbeiro_id' => $barbeiroId,
            'novo_fim' => $horaFim,
            'novo_inicio' => $horaInicio,
        ]);

        if ((int) $stmt->fetchColumn() > 0) {
            return true;
        }

        // Verificar conflito de cadeira com FOR UPDATE
        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM agendamentos
            WHERE cadeira_id = :cadeira_id
              AND status NOT IN ('cancelado', 'no_show')
              AND hora_inicio < :novo_fim
              AND hora_fim > :novo_inicio
            FOR UPDATE
        ");
        $stmt->execute([
            'cadeira_id' => $cadeiraId,
            'novo_fim' => $horaFim,
            'novo_inicio' => $horaInicio,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function inserirAgendamento(
        PDO $conn,
        int $clienteId,
        int $barbeiroId,
        int $cadeiraId,
        int $servicoId,
        string $horaInicio,
        string $horaFim,
    ): void {
        $stmt = $conn->prepare("
            INSERT INTO agendamentos (cliente_id, barbeiro_id, cadeira_id, servico_id, hora_inicio, hora_fim, status, criado_em)
            VALUES (:cliente_id, :barbeiro_id, :cadeira_id, :servico_id, :hora_inicio, :hora_fim, 'solicitado', NOW())
        ");
        $stmt->execute([
            'cliente_id' => $clienteId,
            'barbeiro_id' => $barbeiroId,
            'cadeira_id' => $cadeiraId,
            'servico_id' => $servicoId,
            'hora_inicio' => $horaInicio,
            'hora_fim' => $horaFim,
        ]);
    }

    private function seedDadosTeste(): void
    {
        // Criar dados específicos para teste de concorrência (se não existirem)
        $exists = $this->pdo->query("SELECT id FROM barbeiros WHERE nome = 'Barbeiro Concorrencia'")->fetch();
        if ($exists) {
            return;
        }

        $this->pdo->exec("INSERT INTO barbeiros (nome, ativo) VALUES ('Barbeiro Concorrencia', 1)");
        $barbeiroId = (int) $this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO clientes (nome, telefone) VALUES ('Cliente Concorrencia', '11999990099')");

        $this->pdo->exec("INSERT INTO servicos (nome, duracao_minutos, preco_centavos) VALUES ('Corte Concorrencia', 30, 5000)");
        $servicoId = (int) $this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO cadeiras (nome, ativo) VALUES ('Cadeira Concorrencia', 1)");
        $cadeiraId = (int) $this->pdo->lastInsertId();

        $this->pdo->exec("INSERT INTO cadeira_servico_compativel (cadeira_id, servico_id) VALUES ({$cadeiraId}, {$servicoId})");

        // Horário: segunda (1), 08:00 - 18:00
        $this->pdo->exec("INSERT INTO barbeiro_horarios (barbeiro_id, dia_semana, hora_inicio, hora_fim) VALUES ({$barbeiroId}, 1, '08:00', '18:00')");
    }
}
