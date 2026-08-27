<?php
use function App\Http\e;

$titulo = 'Clientes';
ob_start();
?>

<div class="page-header">
    <h1>👤 Clientes</h1>
</div>

<div class="card">
    <form method="POST" action="/clientes" class="form-inline">
        <div class="form-group" style="flex:2">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" placeholder="Ex: Maria Silva" required>
        </div>
        <div class="form-group" style="flex:1">
            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone" placeholder="(11) 99999-0000" required>
        </div>
        <button type="submit" class="btn btn-primary">Cadastrar</button>
    </form>
</div>

<?php if (empty($clientes)): ?>
    <div class="empty-state"><p>Nenhum cliente cadastrado</p></div>
<?php else: ?>
    <table>
        <thead><tr><th>ID</th><th>Nome</th><th>Telefone</th><th>Ações</th></tr></thead>
        <tbody>
            <?php foreach ($clientes as $c): ?>
            <tr>
                <td><?= $c->id ?></td>
                <td><?= e($c->nome) ?></td>
                <td><?= e($c->telefone) ?></td>
                <td>
                    <form method="POST" action="/clientes/<?= $c->id ?>" style="display:inline">
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
