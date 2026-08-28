<?php
use function App\Http\e;

$titulo = 'Minha Agenda';
ob_start();
?>

<div class="page-header">
    <h1>📅 Minha Agenda — <?= e($barbeiro->nome) ?></h1>
</div>

<form class="filter-bar" method="GET" action="/minha-agenda">
    <div class="form-group">
        <label for="data">Data</label>
        <input type="date" id="data" name="data" value="<?= e($data) ?>">
    </div>
    <button type="submit" class="btn btn-primary">Filtrar</button>
</form>

<?php if (empty($agendamentos)): ?>
    <div class="empty-state">
        <p>Nenhum agendamento em <?= e($data) ?></p>
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
                    <?php if ($ag->status()->value === 'confirmado'): ?>
                        <form method="POST" action="/agendamentos/<?= $ag->id ?>/transitar" style="display:inline">
                            <input type="hidden" name="status" value="em_atendimento">
                            <button class="btn btn-sm btn-primary">Iniciar</button>
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

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
