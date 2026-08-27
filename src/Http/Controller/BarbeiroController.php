<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Entity\Barbeiro;
use App\Domain\Repository\BarbeiroRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;

use function App\Http\e;

final class BarbeiroController
{
    public function __construct(
        private readonly BarbeiroRepositoryInterface $repo,
    ) {
    }

    public function index(Request $request): Response
    {
        $barbeiros = $this->repo->listarTodos();
        $html = View::render('barbeiros/index', ['barbeiros' => $barbeiros]);
        return (new Response())->html($html);
    }

    public function store(Request $request): Response
    {
        $nome = trim($request->input('nome', ''));

        if ($nome === '') {
            return (new Response())->redirect('/barbeiros?erro=Nome+é+obrigatório');
        }

        $this->repo->salvar(Barbeiro::criar($nome));

        return (new Response())->redirect('/barbeiros?sucesso=Barbeiro+cadastrado');
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->repo->excluir($id);

        return (new Response())->redirect('/barbeiros?sucesso=Barbeiro+removido');
    }

    // API JSON
    public function apiIndex(Request $request): Response
    {
        $barbeiros = $this->repo->listarAtivos();
        $data = array_map(fn(Barbeiro $b) => [
            'id' => $b->id,
            'nome' => $b->nome,
            'ativo' => $b->ativo,
        ], $barbeiros);

        return (new Response())->json($data);
    }
}
