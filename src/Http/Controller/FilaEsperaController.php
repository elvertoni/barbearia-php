<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\UseCase\EntrarFilaEsperaUseCase;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Domain\Repository\FilaEsperaRepositoryInterface;
use App\Domain\Repository\ServicoRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;

final class FilaEsperaController
{
    public function __construct(
        private readonly EntrarFilaEsperaUseCase $entrarUseCase,
        private readonly FilaEsperaRepositoryInterface $filaRepo,
        private readonly ClienteRepositoryInterface $clienteRepo,
        private readonly ServicoRepositoryInterface $servicoRepo,
    ) {
    }

    public function index(Request $request): Response
    {
        $data = $request->query('data', date('Y-m-d'));
        $itens = $this->filaRepo->listarPorData($data);
        $clientes = $this->clienteRepo->listarTodos();
        $servicos = $this->servicoRepo->listarAtivos();

        $html = View::render('fila_espera/index', [
            'itens' => $itens,
            'clientes' => $clientes,
            'servicos' => $servicos,
            'data' => $data,
        ]);

        return (new Response())->html($html);
    }

    public function store(Request $request): Response
    {
        $clienteId = (int) $request->input('cliente_id', 0);
        $servicoId = (int) $request->input('servico_id', 0);
        $dataDesejada = trim($request->input('data_desejada', ''));

        if ($clienteId <= 0 || $servicoId <= 0 || $dataDesejada === '') {
            return (new Response())->redirect('/fila-espera?erro=Preencha+todos+os+campos');
        }

        $this->entrarUseCase->executar($clienteId, $servicoId, $dataDesejada);

        return (new Response())->redirect('/fila-espera?sucesso=Adicionado+à+fila+de+espera&data=' . $dataDesejada);
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->filaRepo->remover($id);

        return (new Response())->redirect('/fila-espera?sucesso=Removido+da+fila');
    }
}
