<?php

declare(strict_types=1);

namespace LaravelDfd\Builder;

use LaravelDfd\IR\DataFlow;
use LaravelDfd\IR\DataStoreNode;
use LaravelDfd\IR\ExternalEntityNode;
use LaravelDfd\IR\ProcessNode;
use LaravelDfd\Parser\ASTParser;
use LaravelDfd\Parser\ASTTraverser;
use LaravelDfd\Scanner\ModelScanner;
use LaravelDfd\Scanner\RouteScanner;
use ReflectionClass;
use Throwable;

final class DFDBuilder
{
    public function __construct(
        private RouteScanner $routeScanner = new RouteScanner(),
        private ASTParser $astParser = new ASTParser(),
        private ASTTraverser $astTraverser = new ASTTraverser(),
        private ModelScanner $modelScanner = new ModelScanner(),
    ) {
    }

    /**
     * @return array{
     *     processes: array<int, ProcessNode>,
     *     dataStores: array<int, DataStoreNode>,
     *     externalEntities: array<int, ExternalEntityNode>,
     *     flows: array<int, DataFlow>
     * }
     */
    public function build(): array
    {
        $processes = [];
        $dataStores = [];
        $externalEntities = [];
        $flows = [];
        $storeNumbers = [];

        $client = new ExternalEntityNode('external.client', 'User');
        $externalEntities[$client->getId()] = $client;

        foreach ($this->routeScanner->scan() as $route) {
            if (($route['action'] ?? null) === null || ($route['action'] ?? null) === 'Closure') {
                continue;
            }

            if (! $this->isBusinessRoute($route['uri'])) {
                continue;
            }

            $processId = $this->processId($route['action'] ?? null, $route['uri']);
            $analysis = $this->analyzeController($route['action'] ?? null);

            $process = new ProcessNode(
                $processId,
                $this->processName($route['action'] ?? null, $route['uri']),
                [$this->routeInput($route['methods'], $route['uri'])],
                $analysis['calls'],
            );

            $processes[$process->getId()] = $process;
            $flows[$client->getId() . '->' . $process->getId()] = new DataFlow($client->getId(), $process->getId(), $this->businessFlowLabel($route['uri']));

            foreach ($analysis['models'] as $model) {
                $key = $this->storeKey($model);
                $storeNumbers[$key] ??= count($storeNumbers) + 1;
                $store = new DataStoreNode('store.' . $key, 'D' . $storeNumbers[$key] . ' ' . $this->storeLabel($model), 'model');
                $dataStores[$store->getId()] = $store;
                $flows[$process->getId() . '->' . $store->getId()] = new DataFlow($process->getId(), $store->getId(), $this->storeLabel($model));
            }

            foreach ($analysis['tables'] as $table) {
                if ($this->isInternalTable($table)) {
                    continue;
                }

                $key = $this->storeKey($table);
                $storeNumbers[$key] ??= count($storeNumbers) + 1;
                $store = new DataStoreNode('store.' . $key, 'D' . $storeNumbers[$key] . ' ' . $this->storeLabel($table), 'database_table');
                $dataStores[$store->getId()] = $store;
                $flows[$process->getId() . '->' . $store->getId()] = new DataFlow($process->getId(), $store->getId(), $this->storeLabel($table));
            }

            if ($this->usesExternalPaymentGateway($analysis['calls'], $route['uri'])) {
                $gateway = new ExternalEntityNode('external.payment_gateway', 'Payment Gateway');
                $externalEntities[$gateway->getId()] = $gateway;
                $flows[$process->getId() . '->' . $gateway->getId()] = new DataFlow($process->getId(), $gateway->getId(), 'Payment Request');
                $flows[$gateway->getId() . '->' . $process->getId()] = new DataFlow($gateway->getId(), $process->getId(), 'Payment Status');
            }
        }

        return [
            'processes' => array_values($processes),
            'dataStores' => array_values($dataStores),
            'externalEntities' => array_values($externalEntities),
            'flows' => array_values($flows),
        ];
    }

    /**
     * @return array{calls: array<int, string>, models: array<int, string>, tables: array<int, string>}
     */
    private function analyzeController(?string $action): array
    {
        $file = $this->controllerFile($action);

        if ($file === null) {
            return [
                'calls' => [],
                'models' => [],
                'tables' => [],
            ];
        }

        $ast = $this->astParser->parseFile($file);
        $nodes = $this->astTraverser->traverse($ast);
        $modelScan = $this->modelScanner->scanAst($ast);

        return [
            'calls' => $this->callOutputs($nodes),
            'models' => $modelScan['models'],
            'tables' => $modelScan['tables'],
        ];
    }

    private function controllerFile(?string $action): ?string
    {
        $controller = $this->controllerClass($action);

        if ($controller === null || ! class_exists($controller)) {
            return null;
        }

        try {
            $file = (new ReflectionClass($controller))->getFileName();
        } catch (Throwable) {
            return null;
        }

        return is_string($file) ? $file : null;
    }

    private function controllerClass(?string $action): ?string
    {
        if ($action === null || $action === 'Closure') {
            return null;
        }

        if (str_contains($action, '@')) {
            return explode('@', $action, 2)[0];
        }

        return class_exists($action) ? $action : null;
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     * @return array<int, string>
     */
    private function callOutputs(array $nodes): array
    {
        $calls = [];

        foreach ($nodes as $node) {
            if (! in_array($node['type'] ?? null, ['StaticCall', 'MethodCall', 'FunctionCall'], true)) {
                continue;
            }

            $name = $node['name'] ?? null;
            $target = $node['target'] ?? null;
            $call = is_string($target) && $target !== '' ? $target . '.' . $name : $name;

            if (is_string($call) && $call !== '') {
                $calls[$call] = true;
            }
        }

        return array_values(array_keys($calls));
    }

    /**
     * @param array<int, string> $methods
     */
    private function routeInput(array $methods, string $uri): string
    {
        return implode('|', $methods) . ' ' . $uri;
    }

    private function processId(?string $action, string $uri): string
    {
        return 'process.' . $this->slug($action ?: $uri);
    }

    private function processName(?string $action, string $uri): string
    {
        return $action ?: $uri;
    }

    private function isBusinessRoute(string $uri): bool
    {
        $uri = trim($uri, '/');

        if ($uri === '' || $uri === 'favicon.ico') {
            return false;
        }

        foreach (['_ignition', 'telescope', 'sanctum', 'horizon', 'debugbar', 'vendor'] as $prefix) {
            if ($uri === $prefix || str_starts_with($uri, $prefix . '/')) {
                return false;
            }
        }

        return true;
    }

    private function isInternalTable(string $table): bool
    {
        return in_array(strtolower($table), [
            'jobs',
            'failed_jobs',
            'migrations',
            'cache',
            'cache_locks',
            'sessions',
            'password_reset_tokens',
            'personal_access_tokens',
        ], true);
    }

    /**
     * @param array<int, string> $calls
     */
    private function usesExternalPaymentGateway(array $calls, string $uri): bool
    {
        $haystack = strtolower($uri . ' ' . implode(' ', $calls));

        return str_contains($haystack, 'http.post')
            || str_contains($haystack, 'http::post')
            || str_contains($haystack, 'curl')
            || str_contains($haystack, 'payment')
            || str_contains($haystack, 'midtrans')
            || str_contains($haystack, 'stripe')
            || str_contains($haystack, 'paypal');
    }

    private function businessFlowLabel(string $uri): string
    {
        $uri = strtolower(trim($uri, '/'));

        if (str_contains($uri, 'login')) {
            return 'Credential Login';
        }

        if (str_contains($uri, 'checkout')) {
            return 'Data Checkout';
        }

        if (str_contains($uri, 'payment')) {
            return 'Data Pembayaran';
        }

        if (str_contains($uri, 'transaction')) {
            return 'Data Transaksi';
        }

        if (str_contains($uri, 'product')) {
            return 'Data Produk';
        }

        return 'Request Bisnis';
    }

    private function storeKey(string $value): string
    {
        $value = preg_replace('/(?<!^)[A-Z]/', '_$0', $value) ?: $value;
        $value = strtolower(str_replace(['-', ' '], '_', $value));
        $value = preg_replace('/[^a-z0-9_]+/', '_', $value) ?: $value;

        $value = trim($value, '_') ?: 'data';

        return rtrim($value, 's') ?: $value;
    }

    private function storeLabel(string $value): string
    {
        $value = preg_replace('/(?<!^)[A-Z]/', ' $0', $value) ?: $value;
        $value = str_replace(['_', '-'], ' ', $value);
        $value = ucwords(trim($value));

        if ($value !== '' && ! str_ends_with(strtolower($value), 's')) {
            return $value . 's';
        }

        return $value;
    }

    private function slug(string $value): string
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/', '.', $value);

        return trim((string) $slug, '.');
    }
}
