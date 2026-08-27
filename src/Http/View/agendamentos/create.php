<?php
use function App\Http\e;

$titulo = 'Novo Agendamento';
ob_start();
?>

<div class="page-header">
    <h1>+ Novo Agendamento</h1>
    <a href="/agendamentos" class="btn btn-outline">← Voltar</a>
</div>

<div class="card">
    <form method="POST" action="/agendamentos">
        <div class="form-grid">
            <div class="form-group">
                <label for="cliente_id">Cliente</label>
                <select id="cliente_id" name="cliente_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= $c->id ?>"><?= e($c->nome) ?> (<?= e($c->telefone) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="barbeiro_id">Barbeiro</label>
                <select id="barbeiro_id" name="barbeiro_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($barbeiros as $b): ?>
                        <option value="<?= $b->id ?>"><?= e($b->nome) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="servico_id">Serviço</label>
                <select id="servico_id" name="servico_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($servicos as $s): ?>
                        <option value="<?= $s->id ?>"><?= e($s->nome) ?> (<?= $s->duracaoMinutos ?>min - <?= e($s->precoFormatado()) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="hora_inicio">Data e Hora</label>
                <input type="datetime-local" id="hora_inicio" name="hora_inicio" required>
            </div>
        </div>

        <div class="mt-2">
            <button type="submit" class="btn btn-primary">Criar Agendamento</button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
