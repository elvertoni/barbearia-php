<?php
$titulo = 'Entrar';
ob_start();
?>

<div class="page-header">
    <h1>🔐 Entrar</h1>
</div>

<div class="card" style="max-width:420px;margin:0 auto">
    <form method="POST" action="/login">
        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" autocomplete="username" required autofocus>
        </div>
        <div class="form-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" autocomplete="current-password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%">Entrar</button>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
