<?php
use function App\Http\e;

$titulo = 'Serviços';
ob_start();
?>

<div class="page-header">
    <h1>✂️ Serviços</h1>
</div>

<div class="card">
    <form method="POST" action="/servicos" class="form-inline">
        <div class="form-group" style="flex:2">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" placeholder="Ex: Corte masculino" required>
        </div>
        <div class="form-group" style="flex:1">
            <label for="duracao_minutos">Duração (min)</label>
            <input type="number" id="duracao_minutos" name="duracao_minutos" placeholder="30" min="5" required>
        </div>
        <div class="form-group" style="flex:1">
            <label for="preco">Preço (R$)</label>
            <input type="text" id="preco" name="preco" placeholder="50,00" required>
        </div>
        <button type="submit" class="btn btn-primary">Cadastrar</button>
    </form>
</div>

<?php if (empty($servicos)): ?>
    <div class="empty-state"><p>Nenhum serviço cadastrado</p></div>
<?php else: ?>
    <table>
        <thead><tr><th>ID</th><th>Nome</th><th>Duração</th><th>Preço</th><th>Ações</th></tr></thead>
        <tbody>
            <?php foreach ($servicos as $s): ?>
            <tr>
                <td><?= $s->id ?></td>
                <td><?= e($s->nome) ?></td>
                <td><?= $s->duracaoMinutos ?> min</td>
                <td><?= e($s->precoFormatado()) ?></td>
                <td>
                    <form method="POST" action="/servicos/<?= $s->id ?>" style="display:inline">
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
