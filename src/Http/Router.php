<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Router simples com suporte a parâmetros dinâmicos e middleware por rota.
 *
 * Exemplo:
 *   $router->get('/barbeiros', [BarbeiroController::class, 'index']);
 *   $router->get('/barbeiros/{id}', [BarbeiroController::class, 'show']);
 *   $router->post('/barbeiros', [BarbeiroController::class, 'store'], [$autenticar, $autorizar]);
 *
 * Cada middleware é um callable(Request): ?Response — retornar uma Response
 * interrompe a cadeia e o handler não é chamado.
 */
final class Router
{
    /** @var array<string, array<string, array{handler: callable|array, middleware: list<callable>}>> */
    private array $routes = [];

    /** @param list<callable> $middleware */
    public function get(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    /** @param list<callable> $middleware */
    public function post(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    /** @param list<callable> $middleware */
    public function put(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /** @param list<callable> $middleware */
    public function delete(string $path, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /** @param list<callable> $middleware */
    private function addRoute(string $method, string $path, callable|array $handler, array $middleware): void
    {
        // Converter {param} para regex nomeado
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[$method][$pattern] = [
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * Resolve a rota para o método e URI dados.
     *
     * @return array{handler: callable|array, params: array<string, string>, middleware: list<callable>}|null
     */
    public function resolve(string $method, string $uri): ?array
    {
        // Normalizar: remover query string e trailing slash
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        // Suporte a form method override (_method)
        if ($method === 'POST') {
            $override = $_POST['_method'] ?? '';
            if (in_array(strtoupper($override), ['PUT', 'DELETE'], true)) {
                $method = strtoupper($override);
            }
        }

        $routesForMethod = $this->routes[$method] ?? [];

        foreach ($routesForMethod as $pattern => $rota) {
            if (preg_match($pattern, $uri, $matches)) {
                // Filtrar apenas parâmetros nomeados (string keys)
                $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);

                return [
                    'handler' => $rota['handler'],
                    'params' => $params,
                    'middleware' => $rota['middleware'],
                ];
            }
        }

        return null;
    }
}
