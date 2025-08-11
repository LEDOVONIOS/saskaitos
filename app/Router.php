<?php

class Router {
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $pattern, callable $handler): void {
        $this->routes['GET'][$pattern] = $handler;
    }

    public function post(string $pattern, callable $handler): void {
        $this->routes['POST'][$pattern] = $handler;
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = rtrim($uri, '/') === '' ? '/' : rtrim($uri, '/');

        // Exact match first
        foreach ($this->routes[$method] as $pattern => $handler) {
            if ($pattern === $path) {
                $handler();
                return;
            }
        }

        // Regex-like params: define patterns starting with ~ to use preg_match
        foreach ($this->routes[$method] as $pattern => $handler) {
            if (strlen($pattern) > 0 && $pattern[0] === '~') {
                if (preg_match($pattern, $path, $matches)) {
                    $handler($matches);
                    return;
                }
            }
        }

        // 404
        http_response_code(404);
        echo 'Not Found';
    }
}