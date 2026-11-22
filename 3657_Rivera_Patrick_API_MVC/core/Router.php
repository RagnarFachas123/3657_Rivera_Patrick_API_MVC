<?php

namespace Core;

class Router
{
    private array $routes = [];

    public function get(string $route, callable $action): void
    {
        $this->add('GET', $route, $action);
    }

    public function post(string $route, callable $action): void
    {
        $this->add('POST', $route, $action);
    }

    public function put(string $route, callable $action): void
    {
        $this->add('PUT', $route, $action);
    }

    public function delete(string $route, callable $action): void
    {
        $this->add('DELETE', $route, $action);
    }

    private function add(string $method, string $route, callable $action): void
    {
        // /api/comics/ -> /api/comics
        if ($route !== '/') {
            $route = rtrim($route, '/');
        }

        $this->routes[] = [
            'method' => $method,
            'route' => $route,
            'action' => $action
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        // quitar "/" final
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $route) {

            // /api/comics/{id}  ->  /api/comics/(\d+)
            $pattern = preg_quote($route['route'], '#');
            $pattern = str_replace('\{id\}', '(\d+)', $pattern);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $uri, $matches) && $route['method'] === $method) {

                array_shift($matches);

                // ejecuta el metodo del controller, ej: ComicController->show(5)
                call_user_func_array($route['action'], $matches);

                return;
            }
        }

        Response::error('Ruta no encontrada', 404);
    }
}
