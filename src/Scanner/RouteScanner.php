<?php

declare(strict_types=1);

namespace LaravelDfd\Scanner;

use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

final class RouteScanner
{
    /**
     * @return array<int, array{uri: string, methods: array<int, string>, action: string|null}>
     */
    public function scan(): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $this->routeUri($route);

            if ($this->shouldIgnore($uri)) {
                continue;
            }

            $routes[] = [
                'uri' => $uri,
                'methods' => $this->routeMethods($route),
                'action' => $this->routeAction($route),
            ];
        }

        return $routes;
    }

    private function routeUri(object $route): string
    {
        if (method_exists($route, 'uri')) {
            return (string) $route->uri();
        }

        return property_exists($route, 'uri') ? (string) $route->uri : '';
    }

    private function shouldIgnore(string $uri): bool
    {
        $patterns = config('laravel-dfd.ignored_routes', config('dfd.ignored_routes', []));

        foreach ((array) $patterns as $pattern) {
            if (is_string($pattern) && Str::is($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function routeMethods(object $route): array
    {
        $methods = method_exists($route, 'methods') ? $route->methods() : [];

        return array_values(array_map(
            static fn (string $method): string => strtoupper($method),
            array_filter((array) $methods, 'is_string'),
        ));
    }

    private function routeAction(object $route): ?string
    {
        if (method_exists($route, 'getActionName')) {
            $action = $route->getActionName();

            if (is_string($action) && $action !== '') {
                return $action;
            }
        }

        if (! method_exists($route, 'getAction')) {
            return null;
        }

        $action = $route->getAction();
        $uses = is_array($action) ? ($action['uses'] ?? null) : null;

        if (is_string($uses)) {
            return $uses;
        }

        if (is_array($uses) && count($uses) === 2) {
            return $this->callableAction($uses);
        }

        if ($uses instanceof Closure) {
            return 'Closure';
        }

        return null;
    }

    /**
     * @param array{0: mixed, 1: mixed} $uses
     */
    private function callableAction(array $uses): ?string
    {
        [$controller, $method] = $uses;

        if (is_object($controller)) {
            $controller = $controller::class;
        }

        if (is_string($controller) && is_string($method)) {
            return $controller . '@' . $method;
        }

        return null;
    }
}
