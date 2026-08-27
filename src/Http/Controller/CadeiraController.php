<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Entity\Cadeira;
use App\Domain\Repository\CadeiraRepositoryInterface;
use App\Domain\Repository\ServicoRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;

final class CadeiraController
{
    public function __construct(
        private readonly CadeiraRepositoryInterface $cadeiraRepo,
        private readonly ServicoRepositoryInterface $servicoRepo,
    ) {
    }

    public function index(Request $request): Response
    {
        $cadeiras = $this->cadeiraRepo->listarTodas();
        $servicos = $this->servicoRepo->listarAtivos();
        $html = View::render('cadeiras/index', [
            'cadeiras' => $cadeiras,
            'servicos' => $servicos,
        ]);
        return (new Response())->html($html);
    }

    public function store(Request $request): Response
    {
        $nome = trim($request->input('nome', ''));
        $servicoIds = $request->input('servicos', []);

        if ($nome === '') {
            return (new Response())->redirect('/cadeiras?erro=Nome+é+obrigatório');
        }

        $cadeira = $this->cadeiraRepo->salvar(Cadeira::criar($nome));

        // Vincular serviços compatíveis
        if (is_array($servicoIds)) {
            foreach ($servicoIds as $servicoId) {
                $this->cadeiraRepo->vincularServico($cadeira->id, (int) $servicoId);
            }
        }

        return (new Response())->redirect('/cadeiras?sucesso=Cadeira+cadastrada');
    }

    public function destroy(Request $request): Response
    {
        $id = (int) $request->param('id');
        $this->cadeiraRepo->excluir($id);

        return (new Response())->redirect('/cadeiras?sucesso=Cadeira+removida');
    }
}
