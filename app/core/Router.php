<?php

namespace App\core;

class Router
{
    private $routes_ahjr = [];
    private $basePath_ahjr;

    public function __construct()
    {
        $scriptName_ahjr = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        $this->basePath_ahjr = rtrim($scriptName_ahjr, '/');
    }

    public function add_ahjr(string $method_ahjr, string $path_ahjr, callable $callback_ahjr): void
    {
        $this->routes_ahjr[] = [
            'method' => $method_ahjr,
            'path' => $path_ahjr,
            'callback' => $callback_ahjr
        ];
    }

    public function run_ahjr(): void
    {
        $uri_ahjr = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method_ahjr = $_SERVER['REQUEST_METHOD'];

        // Eliminar el prefijo (por ejemplo /submate-backend/public)
        if (strpos($uri_ahjr, $this->basePath_ahjr) === 0) {
            $uri_ahjr = substr($uri_ahjr, strlen($this->basePath_ahjr));
        }
        $uri_ahjr = '/' . trim($uri_ahjr, '/');

        foreach ($this->routes_ahjr as $route_ahjr) {
            if ($route_ahjr['method'] === $method_ahjr && preg_match("#^{$route_ahjr['path']}$#", $uri_ahjr, $matches_ahjr)) {
                // Elimina el primer elemento ($matches_ahjr[0]) que es toda la ruta
                array_shift($matches_ahjr);
                // Pasa los parámetros capturados al callback
                call_user_func_array($route_ahjr['callback'], $matches_ahjr);
                return;
            }
        }

        Response::notFound_ahjr("Ruta no encontrada: {$uri_ahjr}");
    }
}
