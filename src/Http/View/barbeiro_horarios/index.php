<?php
use function App\Http\e;

$titulo = 'Horários - ' . $barbeiro->nome;
ob_start();
?>

<div class="page-header">
    <h1>🕐 Horários de <?= e($barbeiro->nome) ?></h1>
    <a href="/barbeiros" class="btn btn-outline">← Voltar</a>
</div>

<div class="card">
    <form method="POST" action="/barbeiros/<?= $barbeiro->id ?>/horarios" class="form-inline">
        <div class="form-group">
            <label for="dia_semana">Dia</label>
            <select id="dia_semana" name="dia_semana" required>
                <?php foreach ($diasSemana as $dia): ?>
                    <option value="<?= $dia->value ?>"><?= e($dia->label()) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="hora_inicio">Início</label>
            <input type="time" id="hora_inicio" name="hora_inicio" value="08:00" required>
        </div>
        <div class="form-group">
            <label for="hora_fim">Fim</label>
            <input type="time" id="hora_fim" name="hora_fim" value="18:00" required>
        </div>
        <button type="submit" class="btn btn-primary">Adicionar</button>
    </form>
</div>

<?php if (empty($horarios)): ?>
    <div class="empty-state">
        <p>Nenhum horário cadastrado</p>
    </div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Dia</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($horarios as $h): ?>
            <tr>
                <td><?= e($h->diaSemana->label()) ?></td>
                <td><?= e($h->horaInicio) ?></td>
                <td><?= e($h->horaFim) ?></td>
                <td>
                    <form method="POST" action="/barbeiros/<?= $barbeiro->id ?>/horarios/<?= $h->id ?>" style="display:inline">
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="btn btn-sm btn-danger">Remover</button>
                    </form>
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
