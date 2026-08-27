<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Application\UseCase\CancelarAgendamentoUseCase;
use App\Application\UseCase\CriarAgendamentoUseCase;
use App\Application\UseCase\EntrarFilaEsperaUseCase;
use App\Application\UseCase\ListarAgendaPorBarbeiroDiaUseCase;
use App\Application\UseCase\TransitarStatusUseCase;
use App\Http\Controller\AgendamentoController;
use App\Http\Controller\BarbeiroController;
use App\Http\Controller\BarbeiroHorarioController;
use App\Http\Controller\CadeiraController;
use App\Http\Controller\ClienteController;
use App\Http\Controller\FilaEsperaController;
use App\Http\Controller\ServicoController;
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

// Use Cases
$criarAgendamento = new CriarAgendamentoUseCase($pdo, $agendamentoRepo, $horarioRepo, $cadeiraRepo, $servicoRepo);
$cancelarAgendamento = new CancelarAgendamentoUseCase($pdo, $agendamentoRepo, $filaEsperaRepo);
$transitarStatus = new TransitarStatusUseCase($agendamentoRepo);
$entrarFila = new EntrarFilaEsperaUseCase($filaEsperaRepo);
$listarAgenda = new ListarAgendaPorBarbeiroDiaUseCase($agendamentoRepo);

// Controllers
$barbeiroCtrl = new BarbeiroController($barbeiroRepo);
$servicoCtrl = new ServicoController($servicoRepo);
$cadeiraCtrl = new CadeiraController($cadeiraRepo, $servicoRepo);
$clienteCtrl = new ClienteController($clienteRepo);
$horarioCtrl = new BarbeiroHorarioController($horarioRepo, $barbeiroRepo);
$agendamentoCtrl = new AgendamentoController(
    $criarAgendamento, $cancelarAgendamento, $transitarStatus,
    $listarAgenda, $barbeiroRepo, $clienteRepo, $servicoRepo,
);
$filaCtrl = new FilaEsperaController($entrarFila, $filaEsperaRepo, $clienteRepo, $servicoRepo);

// =====================================================================
// Rotas
// =====================================================================

$router = new Router();

// Home → redireciona para agendamentos
$router->get('/', fn() => (new Response())->redirect('/agendamentos'));

// Barbeiros
$router->get('/barbeiros', [$barbeiroCtrl, 'index']);
$router->post('/barbeiros', [$barbeiroCtrl, 'store']);
$router->delete('/barbeiros/{id}', [$barbeiroCtrl, 'destroy']);

// Barbeiro Horários
$router->get('/barbeiros/{barbeiro_id}/horarios', [$horarioCtrl, 'index']);
$router->post('/barbeiros/{barbeiro_id}/horarios', [$horarioCtrl, 'store']);
$router->delete('/barbeiros/{barbeiro_id}/horarios/{id}', [$horarioCtrl, 'destroy']);

// Serviços
$router->get('/servicos', [$servicoCtrl, 'index']);
$router->post('/servicos', [$servicoCtrl, 'store']);
$router->delete('/servicos/{id}', [$servicoCtrl, 'destroy']);

// Cadeiras
$router->get('/cadeiras', [$cadeiraCtrl, 'index']);
$router->post('/cadeiras', [$cadeiraCtrl, 'store']);
$router->delete('/cadeiras/{id}', [$cadeiraCtrl, 'destroy']);

// Clientes
$router->get('/clientes', [$clienteCtrl, 'index']);
$router->post('/clientes', [$clienteCtrl, 'store']);
$router->delete('/clientes/{id}', [$clienteCtrl, 'destroy']);

// Agendamentos
$router->get('/agendamentos', [$agendamentoCtrl, 'index']);
$router->get('/agendamentos/novo', [$agendamentoCtrl, 'create']);
$router->post('/agendamentos', [$agendamentoCtrl, 'store']);
$router->post('/agendamentos/{id}/cancelar', [$agendamentoCtrl, 'cancelar']);
$router->post('/agendamentos/{id}/transitar', [$agendamentoCtrl, 'transitar']);

// Fila de Espera
$router->get('/fila-espera', [$filaCtrl, 'index']);
$router->post('/fila-espera', [$filaCtrl, 'store']);
$router->delete('/fila-espera/{id}', [$filaCtrl, 'destroy']);

// API JSON
$router->get('/api/barbeiros', [$barbeiroCtrl, 'apiIndex']);
$router->get('/api/servicos', [$servicoCtrl, 'apiIndex']);

// =====================================================================
// Dispatch
// =====================================================================

$request = new Request();
$method = $request->method();
$uri = $request->uri();

$route = $router->resolve($method, $uri);

if ($route === null) {
    $response = new Response();
    $response->setStatusCode(404);
    $response->html('<h1>404 — Página não encontrada</h1>');
    $response->send();
    exit;
}

$request->setRouteParams($route['params']);
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
