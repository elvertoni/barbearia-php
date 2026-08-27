<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Entity\Servico;
use App\Domain\Repository\ServicoRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;

final class ServicoController
{
    public function __construct(
        private readonly ServicoRepositoryInterface $repo,
    ) {
    }

    public function index(Request $request): Response
    {
        $servicos = $this->repo->listarTodos();
        $html = View::render('servicos/index', ['servicos' => $servicos]);
        return (new Response())->html($html);
    }

    public function store(Request $request): Response
    {
        $nome = trim($request->input('nome', ''));
        $duracao = (int) $request->input('duracao_minutos', 0);
        $preco = (int) round((float) str_replace(',', '.', $request->input('preco', '0')) * 100);

        if ($nome === '' || $duracao <= 0 || $preco <= 0) {
            return (new Response())->redirect('/servicos?erro=Preencha+todos+os+campos+corretamente');
        }

        $this->repo->salvar(Servico::criar($nome, $duracao, $preco));

        return (new Response())->redirect('/servicos?sucesso=Serviço+cadastrado');
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->repo->excluir($id);

        return (new Response())->redirect('/servicos?sucesso=Serviço+removido');
    }

    public function apiIndex(Request $request): Response
    {
        $servicos = $this->repo->listarAtivos();
        $data = array_map(fn(Servico $s) => [
            'id' => $s->id,
            'nome' => $s->nome,
            'duracao_minutos' => $s->duracaoMinutos,
            'preco_centavos' => $s->precoCentavos,
            'preco_formatado' => $s->precoFormatado(),
        ], $servicos);

        return (new Response())->json($data);
    }
}
