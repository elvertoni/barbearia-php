<?php
$titulo = 'Acesso negado';
ob_start();
?>

<div class="empty-state">
    <h1>🚫 403 — Acesso negado</h1>
    <p>Seu perfil não tem permissão para acessar esta página.</p>
    <a href="/" class="btn btn-primary">Voltar ao início</a>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
