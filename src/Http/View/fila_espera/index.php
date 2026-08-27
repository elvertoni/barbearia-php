<?php
use function App\Http\e;

$titulo = 'Fila de Espera';
ob_start();
?>

<div class="page-header">
    <h1>⏳ Fila de Espera</h1>
</div>

<div class="card">
    <form method="POST" action="/fila-espera" class="form-inline">
        <div class="form-group" style="flex:1">
            <label for="cliente_id">Cliente</label>
            <select id="cliente_id" name="cliente_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($clientes as $c): ?>
                    <option value="<?= $c->id ?>"><?= e($c->nome) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="flex:1">
            <label for="servico_id">Serviço</label>
            <select id="servico_id" name="servico_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($servicos as $s): ?>
                    <option value="<?= $s->id ?>"><?= e($s->nome) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="data_desejada">Data</label>
            <input type="date" id="data_desejada" name="data_desejada" value="<?= e($data) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Entrar na Fila</button>
    </form>
</div>

<form class="filter-bar" method="GET" action="/fila-espera">
    <div class="form-group">
        <label for="data_filtro">Filtrar por data</label>
        <input type="date" id="data_filtro" name="data" value="<?= e($data) ?>">
    </div>
    <button type="submit" class="btn btn-outline">Filtrar</button>
</form>

<?php if (empty($itens)): ?>
    <div class="empty-state"><p>Fila vazia para <?= e($data) ?></p></div>
<?php else: ?>
    <table>
        <thead><tr><th>#</th><th>Cliente</th><th>Serviço</th><th>Data</th><th>Entrou em</th><th>Ações</th></tr></thead>
        <tbody>
            <?php foreach ($itens as $item): ?>
            <tr>
                <td><?= $item->id ?></td>
                <td><?= e((string) $item->clienteId) ?></td>
                <td><?= e((string) $item->servicoId) ?></td>
                <td><?= e($item->dataDesejada) ?></td>
                <td><?= e($item->criadoEm->format('d/m H:i')) ?></td>
                <td>
                    <form method="POST" action="/fila-espera/<?= $item->id ?>" style="display:inline">
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
