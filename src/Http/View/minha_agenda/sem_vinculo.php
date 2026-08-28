<?php
$titulo = 'Minha Agenda';
ob_start();
?>

<div class="empty-state">
    <h1>📅 Minha Agenda</h1>
    <p>Seu usuário ainda não está vinculado a um barbeiro. Peça ao dono para fazer o vínculo em <strong>Usuários</strong>.</p>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
