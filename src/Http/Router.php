<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Router simples com suporte a parâmetros dinâmicos.
 *
 * Exemplo:
 *   $router->get('/barbeiros', [BarbeiroController::class, 'index']);
 *   $router->get('/barbeiros/{id}', [BarbeiroController::class, 'show']);
 *   $router->post('/barbeiros', [BarbeiroController::class, 'store']);
 */
final class Router
{
    /** @var array<string, array<string, array{handler: callable|array, params: array}>> */
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        // Converter {param} para regex nomeado
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[$method][$pattern] = $handler;
    }

    /**
     * Resolve a rota para o método e URI dados.
     *
     * @return array{handler: callable|array, params: array<string, string>}|null
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

        foreach ($routesForMethod as $pattern => $handler) {
            if (preg_match($pattern, $uri, $matches)) {
                // Filtrar apenas parâmetros nomeados (string keys)
                $params = array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);

                return [
                    'handler' => $handler,
                    'params' => $params,
                ];
            }
        }

        return null;
    }
}
