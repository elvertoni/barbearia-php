<?php
use function App\Http\e;

$titulo = 'Barbeiros';
ob_start();
?>

<div class="page-header">
    <h1>💈 Barbeiros</h1>
</div>

<div class="card">
    <form method="POST" action="/barbeiros" class="form-inline">
        <div class="form-group" style="flex:1">
            <label for="nome">Nome do Barbeiro</label>
            <input type="text" id="nome" name="nome" placeholder="Ex: João Silva" required>
        </div>
        <button type="submit" class="btn btn-primary">Cadastrar</button>
    </form>
</div>

<?php if (empty($barbeiros)): ?>
    <div class="empty-state">
        <p>Nenhum barbeiro cadastrado</p>
    </div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($barbeiros as $b): ?>
            <tr>
                <td><?= $b->id ?></td>
                <td><?= e($b->nome) ?></td>
                <td><span class="badge <?= $b->ativo ? 'badge-confirmado' : 'badge-cancelado' ?>"><?= $b->ativo ? 'Ativo' : 'Inativo' ?></span></td>
                <td class="flex-gap">
                    <a href="/barbeiros/<?= $b->id ?>/horarios" class="btn btn-sm btn-outline">Horários</a>
                    <form method="POST" action="/barbeiros/<?= $b->id ?>" style="display:inline">
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Remover barbeiro?')">Remover</button>
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
