<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de agendamento para barbearia">
    <title><?= htmlspecialchars($titulo ?? 'Barbearia') ?> — Barbearia</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="navbar-brand">✂️ Barbearia</a>
            <ul class="nav-links">
                <li><a href="/agendamentos" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/agendamentos') ? 'active' : '' ?>">Agenda</a></li>
                <li><a href="/agendamentos/novo" class="btn-nav-primary">+ Agendar</a></li>
                <li><a href="/barbeiros" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/barbeiros') ? 'active' : '' ?>">Barbeiros</a></li>
                <li><a href="/servicos" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/servicos') ? 'active' : '' ?>">Serviços</a></li>
                <li><a href="/cadeiras" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/cadeiras') ? 'active' : '' ?>">Cadeiras</a></li>
                <li><a href="/clientes" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/clientes') ? 'active' : '' ?>">Clientes</a></li>
                <li><a href="/fila-espera" class="<?= str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/fila-espera') ? 'active' : '' ?>">Fila</a></li>
            </ul>
        </div>
    </nav>

    <main class="container">
        <?php if (!empty($_GET['sucesso'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['sucesso']) ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['erro'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($_GET['erro']) ?></div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="footer">
        <div class="container">
            <p>Sistema de Agendamento — Projeto de Portfólio | PHP <?= PHP_MAJOR_VERSION ?>.<?= PHP_MINOR_VERSION ?></p>
        </div>
    </footer>
</body>
</html>
