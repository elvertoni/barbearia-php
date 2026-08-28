<?php
use function App\Http\e;

/** @var list<\App\Domain\Enum\PerfilUsuario> $perfis */
$titulo = 'Usuários';
ob_start();
?>

<div class="page-header">
    <h1>👥 Usuários</h1>
</div>

<div class="card">
    <form method="POST" action="/usuarios" class="form-inline">
        <div class="form-group">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" required>
        </div>
        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="senha">Senha (mín. 8)</label>
            <input type="password" id="senha" name="senha" minlength="8" required>
        </div>
        <div class="form-group">
            <label for="perfil">Perfil</label>
            <select id="perfil" name="perfil" required>
                <?php foreach ($perfis as $p): ?>
                    <option value="<?= e($p->value) ?>"><?= e($p->label()) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="barbeiro_id">Vincular a barbeiro (só perfil barbeiro)</label>
            <select id="barbeiro_id" name="barbeiro_id">
                <option value="0">—</option>
                <?php foreach ($barbeirosSemUsuario as $b): ?>
                    <option value="<?= $b->id ?>"><?= e($b->nome) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Criar</button>
    </form>
</div>

<?php if (empty($usuarios)): ?>
    <div class="empty-state"><p>Nenhum usuário cadastrado</p></div>
<?php else: ?>
    <table>
        <thead>
            <tr><th>ID</th><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Status</th><th>Ações</th></tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
            <tr>
                <td><?= $u->id ?></td>
                <td><?= e($u->nome) ?></td>
                <td><?= e($u->email) ?></td>
                <td><?= e($u->perfil->label()) ?></td>
                <td><span class="badge <?= $u->ativo ? 'badge-confirmado' : 'badge-cancelado' ?>"><?= $u->ativo ? 'Ativo' : 'Inativo' ?></span></td>
                <td class="flex-gap">
                    <form method="POST" action="/usuarios/<?= $u->id ?>" style="display:inline">
                        <input type="hidden" name="_method" value="DELETE">
                        <button class="btn btn-sm btn-danger" onclick="return confirm('Remover usuário?')">Remover</button>
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
