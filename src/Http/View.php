<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Renderizador de templates PHP simples.
 *
 * Usa PHP nativo como template engine. Variáveis passadas são extraídas
 * no escopo do template. Output é capturado via ob_start/ob_get_clean.
 *
 * Segurança: o helper e() escapa output HTML para prevenir XSS.
 */
final class View
{
    private static string $basePath = __DIR__ . '/View';
    private static string $layoutPath = '';

    /**
     * Renderiza um template e retorna o HTML.
     *
     * @param string $template Nome do template (ex: 'barbeiros/index')
     * @param array $_viewVariables Dados a passar para o template
     */
    public static function render(string $template, array $_viewVariables = []): string
    {
        $filePath = self::$basePath . '/' . $template . '.php';

        if (!file_exists($filePath)) {
            throw new \RuntimeException("Template não encontrado: {$filePath}");
        }

        // Extrair variáveis no escopo do template
        extract($_viewVariables, EXTR_OVERWRITE);

        ob_start();
        require $filePath;
        $content = ob_get_clean();

        // Se há layout, envolver o conteúdo
        if (self::$layoutPath !== '') {
            $layoutFile = self::$basePath . '/layouts/' . self::$layoutPath . '.php';
            if (file_exists($layoutFile)) {
                ob_start();
                require $layoutFile;
                $content = ob_get_clean();
            }
            self::$layoutPath = '';
        }

        return $content;
    }

    /**
     * Define o layout a ser usado.
     */
    public static function layout(string $layout): void
    {
        self::$layoutPath = $layout;
    }
}

/**
 * Helper global para escapar output HTML (prevenir XSS).
 * Deve ser usado em TODA variável renderizada em templates.
 */
function e(mixed $value): string
{
    if ($value === null) {
        return '';
    }
    if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
}
