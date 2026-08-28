<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\UseCase\ListarAgendaPorBarbeiroDiaUseCase;
use App\Domain\Repository\BarbeiroRepositoryInterface;
use App\Http\Auth\SessaoAtual;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;

/**
 * Controller da agenda do barbeiro logado — enxerga só os próprios agendamentos.
 */
final class MinhaAgendaController
{
    public function __construct(
        private readonly ListarAgendaPorBarbeiroDiaUseCase $listarUseCase,
        private readonly BarbeiroRepositoryInterface $barbeiroRepo,
        private readonly SessaoAtual $sessao,
    ) {
    }

    public function index(Request $request): Response
    {
        $usuarioId = $this->sessao->usuarioId();
        $barbeiro = $usuarioId !== null ? $this->barbeiroRepo->buscarPorUsuarioId($usuarioId) : null;

        if ($barbeiro === null) {
            return (new Response())->html(
                View::render('minha_agenda/sem_vinculo'),
                409,
            );
        }

        $data = $request->query('data', date('Y-m-d'));
        $agendamentos = $this->listarUseCase->executar($barbeiro->id, $data);

        $html = View::render('minha_agenda/index', [
            'barbeiro' => $barbeiro,
            'agendamentos' => $agendamentos,
            'data' => $data,
        ]);

        return (new Response())->html($html);
    }
}
