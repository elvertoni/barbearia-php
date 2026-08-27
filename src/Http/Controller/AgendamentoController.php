<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\UseCase\CancelarAgendamentoUseCase;
use App\Application\UseCase\CriarAgendamentoUseCase;
use App\Application\UseCase\ListarAgendaPorBarbeiroDiaUseCase;
use App\Application\UseCase\TransitarStatusUseCase;
use App\Domain\Enum\StatusAgendamento;
use App\Domain\Repository\BarbeiroRepositoryInterface;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\ServicoRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Controller de Agendamentos — fino, apenas chama Use Cases e traduz respostas.
 * Nenhuma lógica de conflito aqui (proibido pelo PRD).
 */
final class AgendamentoController
{
    public function __construct(
        private readonly CriarAgendamentoUseCase $criarUseCase,
        private readonly CancelarAgendamentoUseCase $cancelarUseCase,
        private readonly TransitarStatusUseCase $transitarUseCase,
        private readonly ListarAgendaPorBarbeiroDiaUseCase $listarUseCase,
        private readonly BarbeiroRepositoryInterface $barbeiroRepo,
        private readonly ClienteRepositoryInterface $clienteRepo,
        private readonly ServicoRepositoryInterface $servicoRepo,
    ) {
    }

    public function index(Request $request): Response
    {
        $barbeiros = $this->barbeiroRepo->listarAtivos();
        $data = $request->query('data', date('Y-m-d'));
        $barbeiroId = $request->query('barbeiro_id');

        $agendamentos = [];
        $barbeiroSelecionado = null;

        if ($barbeiroId) {
            $barbeiroId = (int) $barbeiroId;
            $barbeiroSelecionado = $this->barbeiroRepo->buscarPorId($barbeiroId);
            $agendamentos = $this->listarUseCase->executar($barbeiroId, $data);
        }

        $html = View::render('agendamentos/index', [
            'barbeiros' => $barbeiros,
            'agendamentos' => $agendamentos,
            'data' => $data,
            'barbeiroSelecionado' => $barbeiroSelecionado,
        ]);

        return (new Response())->html($html);
    }

    public function create(Request $request): Response
    {
        $barbeiros = $this->barbeiroRepo->listarAtivos();
        $clientes = $this->clienteRepo->listarTodos();
        $servicos = $this->servicoRepo->listarAtivos();

        $html = View::render('agendamentos/create', [
            'barbeiros' => $barbeiros,
            'clientes' => $clientes,
            'servicos' => $servicos,
        ]);

        return (new Response())->html($html);
    }

    public function store(Request $request): Response
    {
        $clienteId = (int) $request->input('cliente_id', 0);
        $barbeiroId = (int) $request->input('barbeiro_id', 0);
        $servicoId = (int) $request->input('servico_id', 0);
        $dataHora = trim($request->input('hora_inicio', ''));

        if ($clienteId <= 0 || $barbeiroId <= 0 || $servicoId <= 0 || $dataHora === '') {
            return (new Response())->redirect('/agendamentos/novo?erro=Preencha+todos+os+campos');
        }

        try {
            $horaInicio = new DateTimeImmutable($dataHora, new DateTimeZone('UTC'));

            $this->criarUseCase->executar(
                clienteId: $clienteId,
                barbeiroId: $barbeiroId,
                servicoId: $servicoId,
                horaInicio: $horaInicio,
            );

            return (new Response())->redirect('/agendamentos?sucesso=Agendamento+criado&data=' . $horaInicio->format('Y-m-d') . '&barbeiro_id=' . $barbeiroId);
        } catch (\Throwable $e) {
            $erro = urlencode($e->getMessage());
            return (new Response())->redirect("/agendamentos/novo?erro={$erro}");
        }
    }

    public function cancelar(Request $request): Response
    {
        $id = (int) $request->param('id');

        try {
            $resultado = $this->cancelarUseCase->executar($id);
            $msg = 'Agendamento+cancelado';

            if (!empty($resultado['fila_sinalizada'])) {
                $qtd = count($resultado['fila_sinalizada']);
                $msg .= "+({$qtd}+item(ns)+na+fila+sinalizado(s))";
            }

            return (new Response())->redirect('/agendamentos?sucesso=' . $msg);
        } catch (\Throwable $e) {
            $erro = urlencode($e->getMessage());
            return (new Response())->redirect("/agendamentos?erro={$erro}");
        }
    }

    public function transitar(Request $request): Response
    {
        $id = (int) $request->param('id');
        $novoStatus = $request->input('status', '');

        try {
            $status = StatusAgendamento::from($novoStatus);
            $this->transitarUseCase->executar($id, $status);

            return (new Response())->redirect('/agendamentos?sucesso=Status+atualizado+para+' . urlencode($status->value));
        } catch (\Throwable $e) {
            $erro = urlencode($e->getMessage());
            return (new Response())->redirect("/agendamentos?erro={$erro}");
        }
    }
}
