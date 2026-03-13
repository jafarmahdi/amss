<?php

declare(strict_types=1);

namespace App\Support;

class Router
{
    private static ?self $instance = null;

    private array $routes = [];

    private array $namedRoutes = [];

    public function __construct()
    {
        self::$instance = $this;
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Router not initialized.');
        }

        return self::$instance;
    }

    public function get(string $path, array $handler, string $name): void
    {
        $this->add('GET', $path, $handler, $name);
    }

    public function post(string $path, array $handler, string $name): void
    {
        $this->add('POST', $path, $handler, $name);
    }

    public function put(string $path, array $handler, string $name): void
    {
        $this->add('PUT', $path, $handler, $name);
    }

    public function delete(string $path, array $handler, string $name): void
    {
        $this->add('DELETE', $path, $handler, $name);
    }

    public function resource(string $name, string $controllerClass): void
    {
        $base = '/' . trim($name, '/');

        $this->get($base, [$controllerClass, 'index'], $name . '.index');
        $this->get($base . '/create', [$controllerClass, 'create'], $name . '.create');
        $this->post($base, [$controllerClass, 'store'], $name . '.store');
        $this->get($base . '/{id}', [$controllerClass, 'show'], $name . '.show');
        $this->get($base . '/{id}/edit', [$controllerClass, 'edit'], $name . '.edit');
        $this->put($base . '/{id}', [$controllerClass, 'update'], $name . '.update');
        $this->delete($base . '/{id}', [$controllerClass, 'destroy'], $name . '.destroy');
    }

    public function match(string $method, string $path): ?array
    {
        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $matches) !== 1) {
                continue;
            }

            $params = [];
            foreach ($route['params'] as $param) {
                $params[$param] = $matches[$param];
            }

            return [
                'handler' => $route['handler'],
                'name' => $route['name'],
                'params' => $params,
            ];
        }

        return null;
    }

    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException('Unknown route: ' . $name);
        }

        $path = $this->namedRoutes[$name];
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', urlencode((string) $value), $path);
        }

        return $path;
    }

    private function add(string $method, string $path, array $handler, string $name): void
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $path, $matches);
        $params = $matches[1];
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);

        $this->routes[$method][] = [
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
            'name' => $name,
            'params' => $params,
        ];

        $this->namedRoutes[$name] = $path;
    }
}
