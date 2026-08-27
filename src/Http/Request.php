<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Wrapper sobre os dados da requisição HTTP.
 */
final class Request
{
    private array $queryParams;
    private array $postData;
    private array $routeParams;

    public function __construct()
    {
        $this->queryParams = $_GET;
        $this->postData = $_POST;
        $this->routeParams = [];
    }

    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /**
     * Retorna um parâmetro da rota (ex: {id}).
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Retorna um parâmetro da query string.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Retorna um campo do POST.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->postData[$key] ?? $default;
    }

    /**
     * Retorna todos os dados do POST.
     */
    public function all(): array
    {
        return $this->postData;
    }

    /**
     * Lê o body da requisição (para JSON APIs).
     */
    public function jsonBody(): array
    {
        $body = file_get_contents('php://input');
        return json_decode($body ?: '{}', true) ?? [];
    }
}
