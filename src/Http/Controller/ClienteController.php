<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Entity\Cliente;
use App\Domain\Repository\ClienteRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;

final class ClienteController
{
    public function __construct(
        private readonly ClienteRepositoryInterface $repo,
    ) {
    }

    public function index(Request $request): Response
    {
        $clientes = $this->repo->listarTodos();
        $html = View::render('clientes/index', ['clientes' => $clientes]);
        return (new Response())->html($html);
    }

    public function store(Request $request): Response
    {
        $nome = trim($request->input('nome', ''));
        $telefone = trim($request->input('telefone', ''));

        if ($nome === '' || $telefone === '') {
            return (new Response())->redirect('/clientes?erro=Preencha+todos+os+campos');
        }

        $this->repo->salvar(Cliente::criar($nome, $telefone));

        return (new Response())->redirect('/clientes?sucesso=Cliente+cadastrado');
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->repo->excluir($id);

        return (new Response())->redirect('/clientes?sucesso=Cliente+removido');
    }
}
