<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Application\UseCase\AutenticarUsuarioUseCase;
use App\Application\UseCase\CancelarAgendamentoUseCase;
use App\Application\UseCase\CriarAgendamentoUseCase;
use App\Application\UseCase\EntrarFilaEsperaUseCase;
use App\Application\UseCase\ListarAgendaPorBarbeiroDiaUseCase;
use App\Application\UseCase\RegistrarUsuarioUseCase;
use App\Application\UseCase\TransitarStatusUseCase;
use App\Application\UseCase\VincularUsuarioBarbeiroUseCase;
use App\Domain\Enum\PerfilUsuario;
use App\Http\Auth\SessaoAtual;
use App\Http\Controller\AgendamentoController;
use App\Http\Controller\AuthController;
use App\Http\Controller\BarbeiroController;
use App\Http\Controller\BarbeiroHorarioController;
use App\Http\Controller\CadeiraController;
use App\Http\Controller\ClienteController;
use App\Http\Controller\FilaEsperaController;
use App\Http\Controller\MinhaAgendaController;
use App\Http\Controller\ServicoController;
use App\Http\Controller\UsuarioController;
use App\Http\Middleware\Autenticar;
use App\Http\Middleware\AutorizarPerfil;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Repository\PdoAgendamentoRepository;
use App\Infrastructure\Repository\PdoBarbeiroHorarioRepository;
use App\Infrastructure\Repository\PdoBarbeiroRepository;
use App\Infrastructure\Repository\PdoCadeiraRepository;
use App\Infrastructure\Repository\PdoClienteRepository;
use App\Infrastructure\Repository\PdoFilaEsperaRepository;
use App\Infrastructure\Repository\PdoServicoRepository;
use App\Infrastructure\Repository\PdoTentativaLoginRepository;
use App\Infrastructure\Repository\PdoUsuarioRepository;

// =====================================================================
// Sessão — cookie endurecido; Secure só em produção (APP_ENV=prod)
// =====================================================================

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => getenv('APP_ENV') === 'prod',
]);
session_start();

// =====================================================================
// Bootstrapping — DI manual (sem container de framework)
// =====================================================================

try {
    $pdo = Connection::create();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha na conexão com o banco: ' . $e->getMessage()]);
    exit;
}

// Repositórios
$barbeiroRepo = new PdoBarbeiroRepository($pdo);
$cadeiraRepo = new PdoCadeiraRepository($pdo);
$servicoRepo = new PdoServicoRepository($pdo);
$clienteRepo = new PdoClienteRepository($pdo);
$horarioRepo = new PdoBarbeiroHorarioRepository($pdo);
$agendamentoRepo = new PdoAgendamentoRepository($pdo);
$filaEsperaRepo = new PdoFilaEsperaRepository($pdo);
$usuarioRepo = new PdoUsuarioRepository($pdo);
$tentativaLoginRepo = new PdoTentativaLoginRepository($pdo);

// Sessão / autenticação
$sessao = new SessaoAtual($usuarioRepo);

// Use Cases
$criarAgendamento = new CriarAgendamentoUseCase($pdo, $agendamentoRepo, $horarioRepo, $cadeiraRepo, $servicoRepo);
$cancelarAgendamento = new CancelarAgendamentoUseCase($pdo, $agendamentoRepo, $filaEsperaRepo);
$transitarStatus = new TransitarStatusUseCase($agendamentoRepo);
$entrarFila = new EntrarFilaEsperaUseCase($filaEsperaRepo);
$listarAgenda = new ListarAgendaPorBarbeiroDiaUseCase($agendamentoRepo);
$autenticarUsuario = new AutenticarUsuarioUseCase($usuarioRepo, $tentativaLoginRepo);
$registrarUsuario = new RegistrarUsuarioUseCase($usuarioRepo);
$vincularUsuarioBarbeiro = new VincularUsuarioBarbeiroUseCase($barbeiroRepo, $usuarioRepo);

// Controllers
$barbeiroCtrl = new BarbeiroController($barbeiroRepo);
$servicoCtrl = new ServicoController($servicoRepo);
$cadeiraCtrl = new CadeiraController($cadeiraRepo, $servicoRepo);
$clienteCtrl = new ClienteController($clienteRepo);
$horarioCtrl = new BarbeiroHorarioController($horarioRepo, $barbeiroRepo);
$agendamentoCtrl = new AgendamentoController(
    $criarAgendamento, $cancelarAgendamento, $transitarStatus,
    $listarAgenda, $barbeiroRepo, $clienteRepo, $servicoRepo, $sessao,
);
$filaCtrl = new FilaEsperaController($entrarFila, $filaEsperaRepo, $clienteRepo, $servicoRepo);
$authCtrl = new AuthController($autenticarUsuario, $sessao);
$minhaAgendaCtrl = new MinhaAgendaController($listarAgenda, $barbeiroRepo, $sessao);
$usuarioCtrl = new UsuarioController($registrarUsuario, $vincularUsuarioBarbeiro, $usuarioRepo, $barbeiroRepo, $sessao);

// =====================================================================
// Middleware — instâncias reutilizáveis
// =====================================================================

$autenticar = new Autenticar($sessao);
$painel = [$autenticar, new AutorizarPerfil($sessao, PerfilUsuario::Dono, PerfilUsuario::Recepcao)];
$somenteDono = [$autenticar, new AutorizarPerfil($sessao, PerfilUsuario::Dono)];
$somenteBarbeiro = [$autenticar, new AutorizarPerfil($sessao, PerfilUsuario::Barbeiro)];
$operarAgendamento = [$autenticar, new AutorizarPerfil($sessao, PerfilUsuario::Dono, PerfilUsuario::Recepcao, PerfilUsuario::Barbeiro)];

// =====================================================================
// Rotas
// =====================================================================

$router = new Router();

// Home → redireciona conforme perfil
$router->get('/', function () use ($sessao) {
    if (!$sessao->estaLogada()) {
        return (new Response())->redirect('/login');
    }
    $destino = $sessao->perfil() === PerfilUsuario::Barbeiro ? '/minha-agenda' : '/agendamentos';
    return (new Response())->redirect($destino);
});

// Autenticação (sem middleware)
$router->get('/login', [$authCtrl, 'showLogin']);
$router->post('/login', [$authCtrl, 'login']);
$router->post('/logout', [$authCtrl, 'logout']);

// Agenda do barbeiro logado
$router->get('/minha-agenda', [$minhaAgendaCtrl, 'index'], $somenteBarbeiro);

// Gestão de usuários (dono)
$router->get('/usuarios', [$usuarioCtrl, 'index'], $somenteDono);
$router->post('/usuarios', [$usuarioCtrl, 'store'], $somenteDono);
$router->delete('/usuarios/{id}', [$usuarioCtrl, 'destroy'], $somenteDono);

// Barbeiros
$router->get('/barbeiros', [$barbeiroCtrl, 'index'], $painel);
$router->post('/barbeiros', [$barbeiroCtrl, 'store'], $painel);
$router->delete('/barbeiros/{id}', [$barbeiroCtrl, 'destroy'], $painel);

// Barbeiro Horários
$router->get('/barbeiros/{barbeiro_id}/horarios', [$horarioCtrl, 'index'], $painel);
$router->post('/barbeiros/{barbeiro_id}/horarios', [$horarioCtrl, 'store'], $painel);
$router->delete('/barbeiros/{barbeiro_id}/horarios/{id}', [$horarioCtrl, 'destroy'], $painel);

// Serviços
$router->get('/servicos', [$servicoCtrl, 'index'], $painel);
$router->post('/servicos', [$servicoCtrl, 'store'], $painel);
$router->delete('/servicos/{id}', [$servicoCtrl, 'destroy'], $painel);

// Cadeiras
$router->get('/cadeiras', [$cadeiraCtrl, 'index'], $painel);
$router->post('/cadeiras', [$cadeiraCtrl, 'store'], $painel);
$router->delete('/cadeiras/{id}', [$cadeiraCtrl, 'destroy'], $painel);

// Clientes
$router->get('/clientes', [$clienteCtrl, 'index'], $painel);
$router->post('/clientes', [$clienteCtrl, 'store'], $painel);
$router->delete('/clientes/{id}', [$clienteCtrl, 'destroy'], $painel);

// Agendamentos
$router->get('/agendamentos', [$agendamentoCtrl, 'index'], $painel);
$router->get('/agendamentos/novo', [$agendamentoCtrl, 'create'], $painel);
$router->post('/agendamentos', [$agendamentoCtrl, 'store'], $painel);
$router->post('/agendamentos/{id}/cancelar', [$agendamentoCtrl, 'cancelar'], $painel);
$router->post('/agendamentos/{id}/transitar', [$agendamentoCtrl, 'transitar'], $operarAgendamento);

// Fila de Espera
$router->get('/fila-espera', [$filaCtrl, 'index'], $painel);
$router->post('/fila-espera', [$filaCtrl, 'store'], $painel);
$router->delete('/fila-espera/{id}', [$filaCtrl, 'destroy'], $painel);

// API JSON
$router->get('/api/barbeiros', [$barbeiroCtrl, 'apiIndex'], $painel);
$router->get('/api/servicos', [$servicoCtrl, 'apiIndex'], $painel);

// =====================================================================
// Dispatch
// =====================================================================

$request = new Request();
$method = $request->method();
$uri = $request->uri();

$route = $router->resolve($method, $uri);

if ($route === null) {
    (new Response())->html('<h1>404 — Página não encontrada</h1>', 404)->send();
    exit;
}

// Proteção CSRF — toda requisição que muda estado precisa de token válido.
$metodoEfetivo = strtoupper($_POST['_method'] ?? $method);
if (in_array($metodoEfetivo, ['POST', 'PUT', 'DELETE'], true)) {
    if (!$sessao->validarCsrf($_POST['_token'] ?? null)) {
        (new Response())->html(
            '<h1>400 — Requisição inválida</h1><p>Token de segurança ausente ou expirado. Recarregue a página e tente novamente.</p>',
            400,
        )->send();
        exit;
    }
}

$request->setRouteParams($route['params']);

// Pipeline de middleware — uma Response interrompe a cadeia.
foreach ($route['middleware'] as $middleware) {
    $resultado = $middleware($request);
    if ($resultado instanceof Response) {
        $resultado->send();
        exit;
    }
}

$handler = $route['handler'];

try {
    if (is_array($handler)) {
        [$controller, $action] = $handler;
        $response = $controller->$action($request);
    } else {
        $response = $handler($request);
    }
} catch (Throwable $e) {
    $response = new Response();
    $response->setStatusCode(500);
    $response->html('<h1>500 — Erro interno</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

$response->send();
