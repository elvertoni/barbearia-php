<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Domain\Entity\BarbeiroHorario;
use App\Domain\Enum\DiaSemana;
use App\Domain\Repository\BarbeiroHorarioRepositoryInterface;
use App\Domain\Repository\BarbeiroRepositoryInterface;
use App\Http\Request;
use App\Http\Response;
use App\Http\View;

final class BarbeiroHorarioController
{
    public function __construct(
        private readonly BarbeiroHorarioRepositoryInterface $horarioRepo,
        private readonly BarbeiroRepositoryInterface $barbeiroRepo,
    ) {
    }

    public function index(Request $request): Response
    {
        $barbeiroId = (int) $request->param('barbeiro_id');
        $barbeiro = $this->barbeiroRepo->buscarPorId($barbeiroId);

        if ($barbeiro === null) {
            return (new Response())->redirect('/barbeiros?erro=Barbeiro+não+encontrado');
        }

        $horarios = $this->horarioRepo->listarPorBarbeiro($barbeiroId);
        $html = View::render('barbeiro_horarios/index', [
            'barbeiro' => $barbeiro,
            'horarios' => $horarios,
            'diasSemana' => DiaSemana::cases(),
        ]);
        return (new Response())->html($html);
    }

    public function store(Request $request): Response
    {
        $barbeiroId = (int) $request->param('barbeiro_id');
        $diaSemana = (int) $request->input('dia_semana', 0);
        $horaInicio = trim($request->input('hora_inicio', ''));
        $horaFim = trim($request->input('hora_fim', ''));

        if ($diaSemana < 1 || $diaSemana > 7 || $horaInicio === '' || $horaFim === '') {
            return (new Response())->redirect("/barbeiros/{$barbeiroId}/horarios?erro=Preencha+todos+os+campos");
        }

        $horario = new BarbeiroHorario(
            id: null,
            barbeiroId: $barbeiroId,
            diaSemana: DiaSemana::from($diaSemana),
            horaInicio: $horaInicio,
            horaFim: $horaFim,
        );

        try {
            $this->horarioRepo->salvar($horario);
        } catch (\PDOException $e) {
            // Duplicate key (barbeiro já tem horário nesse dia)
            return (new Response())->redirect("/barbeiros/{$barbeiroId}/horarios?erro=Horário+já+cadastrado+para+esse+dia");
        }

        return (new Response())->redirect("/barbeiros/{$barbeiroId}/horarios?sucesso=Horário+cadastrado");
    }

    public function destroy(Request $request): Response
    {
        $barbeiroId = (int) $request->param('barbeiro_id');
        $id = (int) $request->param('id');
        $this->horarioRepo->excluir($id);

        return (new Response())->redirect("/barbeiros/{$barbeiroId}/horarios?sucesso=Horário+removido");
    }
}
