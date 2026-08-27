<?php
use function App\Http\e;

$titulo = 'Agenda';
ob_start();
?>

<div class="page-header">
    <h1>📅 Agenda</h1>
    <a href="/agendamentos/novo" class="btn btn-primary">+ Novo Agendamento</a>
</div>

<form class="filter-bar" method="GET" action="/agendamentos">
    <div class="form-group">
        <label for="data">Data</label>
        <input type="date" id="data" name="data" value="<?= e($data) ?>">
    </div>
    <div class="form-group">
        <label for="barbeiro_id">Barbeiro</label>
        <select id="barbeiro_id" name="barbeiro_id">
            <option value="">Selecione...</option>
            <?php foreach ($barbeiros as $b): ?>
                <option value="<?= $b->id ?>" <?= ($barbeiroSelecionado && $barbeiroSelecionado->id === $b->id) ? 'selected' : '' ?>>
                    <?= e($b->nome) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Filtrar</button>
</form>

<?php if ($barbeiroSelecionado): ?>
    <?php if (empty($agendamentos)): ?>
        <div class="empty-state">
            <p>Nenhum agendamento para <?= e($barbeiroSelecionado->nome) ?> em <?= e($data) ?></p>
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Horário</th>
                    <th>Cliente</th>
                    <th>Serviço</th>
                    <th>Cadeira</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agendamentos as $ag): ?>
                <tr>
                    <td><?= e($ag->horaInicio->format('H:i')) ?> - <?= e($ag->horaFim->format('H:i')) ?></td>
                    <td><?= e((string) $ag->clienteId) ?></td>
                    <td><?= e((string) $ag->servicoId) ?></td>
                    <td><?= e((string) $ag->cadeiraId) ?></td>
                    <td><span class="badge badge-<?= e($ag->status()->value) ?>"><?= e($ag->status()->value) ?></span></td>
                    <td class="flex-gap">
                        <?php if ($ag->status()->value === 'solicitado'): ?>
                            <form method="POST" action="/agendamentos/<?= $ag->id ?>/transitar" style="display:inline">
                                <input type="hidden" name="status" value="confirmado">
                                <button class="btn btn-sm btn-primary">Confirmar</button>
                            </form>
                            <form method="POST" action="/agendamentos/<?= $ag->id ?>/cancelar" style="display:inline">
                                <button class="btn btn-sm btn-danger">Cancelar</button>
                            </form>
                        <?php elseif ($ag->status()->value === 'confirmado'): ?>
                            <form method="POST" action="/agendamentos/<?= $ag->id ?>/transitar" style="display:inline">
                                <input type="hidden" name="status" value="em_atendimento">
                                <button class="btn btn-sm btn-primary">Iniciar</button>
                            </form>
                            <form method="POST" action="/agendamentos/<?= $ag->id ?>/cancelar" style="display:inline">
                                <button class="btn btn-sm btn-danger">Cancelar</button>
                            </form>
                            <form method="POST" action="/agendamentos/<?= $ag->id ?>/transitar" style="display:inline">
                                <input type="hidden" name="status" value="no_show">
                                <button class="btn btn-sm btn-outline">No-show</button>
                            </form>
                        <?php elseif ($ag->status()->value === 'em_atendimento'): ?>
                            <form method="POST" action="/agendamentos/<?= $ag->id ?>/transitar" style="display:inline">
                                <input type="hidden" name="status" value="concluido">
                                <button class="btn btn-sm btn-primary">Concluir</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php else: ?>
    <div class="empty-state">
        <p>Selecione um barbeiro e uma data para ver a agenda</p>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
