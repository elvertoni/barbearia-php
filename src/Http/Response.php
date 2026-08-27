<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Representação da resposta HTTP.
 */
final class Response
{
    private int $statusCode = 200;
    private string $body = '';
    private array $headers = [];

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Resposta JSON.
     */
    public function json(mixed $data, int $statusCode = 200): self
    {
        $this->statusCode = $statusCode;
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        $this->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return $this;
    }

    /**
     * Resposta HTML (renderização de view).
     */
    public function html(string $content, int $statusCode = 200): self
    {
        $this->statusCode = $statusCode;
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        $this->body = $content;
        return $this;
    }

    /**
     * Redireciona para outra URL.
     */
    public function redirect(string $url, int $statusCode = 302): self
    {
        $this->statusCode = $statusCode;
        $this->headers['Location'] = $url;
        return $this;
    }

    /**
     * Envia a resposta para o cliente.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->body;
    }
}
