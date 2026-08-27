<?php
use function App\Http\e;

$titulo = 'Cadeiras';
ob_start();
?>

<div class="page-header">
    <h1>💺 Cadeiras</h1>
</div>

<div class="card">
    <form method="POST" action="/cadeiras">
        <div class="form-inline">
            <div class="form-group" style="flex:1">
                <label for="nome">Nome da Cadeira</label>
                <input type="text" id="nome" name="nome" placeholder="Ex: Cadeira 1 (com pia)" required>
            </div>
            <button type="submit" class="btn btn-primary">Cadastrar</button>
        </div>
        <?php if (!empty($servicos)): ?>
        <div class="mt-1">
            <label class="text-muted" style="font-size:0.85rem">Serviços compatíveis:</label>
            <div class="flex-gap mt-1">
                <?php foreach ($servicos as $s): ?>
                    <label style="display:flex; align-items:center; gap:4px; font-size:0.9rem; cursor:pointer">
                        <input type="checkbox" name="servicos[]" value="<?= $s->id ?>">
                        <?= e($s->nome) ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($cadeiras)): ?>
    <div class="empty-state"><p>Nenhuma cadeira cadastrada</p></div>
<?php else: ?>
    <table>
        <thead><tr><th>ID</th><th>Nome</th><th>Status</th><th>Ações</th></tr></thead>
        <tbody>
            <?php foreach ($cadeiras as $c): ?>
            <tr>
                <td><?= $c->id ?></td>
                <td><?= e($c->nome) ?></td>
                <td><span class="badge <?= $c->ativo ? 'badge-confirmado' : 'badge-cancelado' ?>"><?= $c->ativo ? 'Ativa' : 'Inativa' ?></span></td>
                <td>
                    <form method="POST" action="/cadeiras/<?= $c->id ?>" style="display:inline">
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Remover?')">Remover</button>
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
