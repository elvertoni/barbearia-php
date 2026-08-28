<?php
use function App\Http\e;
use function App\Http\usuario_logado;

$usuarioAtual = usuario_logado();
$perfilAtual = $usuarioAtual['perfil'] ?? null;
$uriAtual = $_SERVER['REQUEST_URI'] ?? '';

/** @var array<int, array{href: string, rotulo: string, prefixo: string, perfis: list<string>}> $itensNav */
$itensNav = [
    ['href' => '/agendamentos', 'rotulo' => 'Agenda', 'prefixo' => '/agendamentos', 'perfis' => ['dono', 'recepcao']],
    ['href' => '/agendamentos/novo', 'rotulo' => '+ Agendar', 'prefixo' => '/agendamentos/novo', 'perfis' => ['dono', 'recepcao']],
    ['href' => '/minha-agenda', 'rotulo' => 'Minha Agenda', 'prefixo' => '/minha-agenda', 'perfis' => ['barbeiro']],
    ['href' => '/barbeiros', 'rotulo' => 'Barbeiros', 'prefixo' => '/barbeiros', 'perfis' => ['dono', 'recepcao']],
    ['href' => '/servicos', 'rotulo' => 'Serviços', 'prefixo' => '/servicos', 'perfis' => ['dono', 'recepcao']],
    ['href' => '/cadeiras', 'rotulo' => 'Cadeiras', 'prefixo' => '/cadeiras', 'perfis' => ['dono', 'recepcao']],
    ['href' => '/clientes', 'rotulo' => 'Clientes', 'prefixo' => '/clientes', 'perfis' => ['dono', 'recepcao']],
    ['href' => '/fila-espera', 'rotulo' => 'Fila', 'prefixo' => '/fila-espera', 'perfis' => ['dono', 'recepcao']],
    ['href' => '/usuarios', 'rotulo' => 'Usuários', 'prefixo' => '/usuarios', 'perfis' => ['dono']],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de gestão para barbearia">
    <title><?= e($titulo ?? 'Barbearia') ?> — Barbearia</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="/" class="navbar-brand">✂️ Barbearia</a>
            <?php if ($usuarioAtual !== null): ?>
                <ul class="nav-links">
                    <?php foreach ($itensNav as $item): ?>
                        <?php if ($perfilAtual !== null && in_array($perfilAtual, $item['perfis'], true)): ?>
                            <li>
                                <a href="<?= e($item['href']) ?>"
                                   class="<?= str_starts_with($uriAtual, $item['prefixo']) ? 'active' : '' ?>">
                                    <?= e($item['rotulo']) ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <div class="nav-user">
                    <span><?= e($usuarioAtual['nome']) ?></span>
                    <form method="POST" action="/logout" style="display:inline">
                        <button type="submit" class="btn btn-sm btn-outline">Sair</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <main class="container">
        <?php if (!empty($_GET['sucesso'])): ?>
            <div class="alert alert-success"><?= e($_GET['sucesso']) ?></div>
        <?php endif; ?>
        <?php if (!empty($_GET['erro'])): ?>
            <div class="alert alert-error"><?= e($_GET['erro']) ?></div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="footer">
        <div class="container">
            <p>Barbearia — sistema de gestão | PHP <?= PHP_MAJOR_VERSION ?>.<?= PHP_MINOR_VERSION ?></p>
        </div>
    </footer>
</body>
</html>
