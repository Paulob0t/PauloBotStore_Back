<?php

namespace App\Core;

class Router
{
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

    public function options(string $path, callable|array $handler): void
    {
        $this->addRoute('OPTIONS', $path, $handler);
    }

    private function addRoute(string $method, string $path, callable|array $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => rtrim($path, '/') ?: '/',
            'handler' => $handler
        ];
    }

    public function dispatch(): void
    {
        // Soporte CORS Preflight
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            Response::applyCorsHeaders();
            http_response_code(204);
            exit;
        }

        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $uri, $params)) {
                $handler = $route['handler'];

                if (is_array($handler)) {
                    [$class, $action] = $handler;
                    $controller = new $class();
                    call_user_func_array([$controller, $action], $params);
                    return;
                }

                if (is_callable($handler)) {
                    call_user_func_array($handler, $params);
                    return;
                }
            }
        }

        Response::error("Ruta no encontrada: [$method] $uri", null, 404);
    }

    private function matchPath(string $routePath, string $uri, &$params = []): bool
    {
        $params = [];
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            foreach ($matches as $key => $val) {
                if (!is_int($key)) {
                    $params[$key] = $val;
                }
            }
            return true;
        }

        return false;
    }
}
