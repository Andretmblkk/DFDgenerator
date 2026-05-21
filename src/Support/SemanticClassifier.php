<?php

declare(strict_types=1);

namespace LaravelDfd\Support;

use LaravelDfd\IR\ProcessNode;

final class SemanticClassifier
{
    /**
     * @param array<string, array<string, mixed>>|null $groups
     */
    public function __construct(private ?array $groups = null)
    {
    }

    /**
     * @return array{key: string, label: string}
     */
    public function classify(ProcessNode $process): array
    {
        $name = $process->getName() . ' ' . implode(' ', $process->getInputs()) . ' ' . implode(' ', $process->getOutputs());
        $semantic = $this->semanticMatch($name);

        if ($semantic !== null) {
            return $semantic;
        }

        $groups = $this->groups();

        foreach ($groups as $key => $group) {
            $controllers = array_filter((array) ($group['controllers'] ?? []), 'is_string');
            $services = array_filter((array) ($group['services'] ?? []), 'is_string');
            $keywords = array_filter((array) ($group['keywords'] ?? []), 'is_string');

            foreach ([...$controllers, ...$services, ...$keywords] as $needle) {
                if ($needle !== '' && stripos($name, $needle) !== false) {
                    return [
                        'key' => $this->slug((string) $key),
                        'label' => (string) ($group['label'] ?? $this->titleFromKey((string) $key)),
                    ];
                }
            }
        }

        return $this->fallback($name);
    }

    /**
     * @return array{key: string, label: string}|null
     */
    private function semanticMatch(string $name): ?array
    {
        $lower = strtolower($name);

        $matches = [
            'authentication' => ['Autentikasi', ['login', 'auth', 'credential', 'register', 'logout']],
            'product' => ['Manajemen Produk', ['product', 'produk', 'catalog', 'kategori', 'category']],
            'checkout' => ['Checkout Produk', ['checkout', 'cart', 'keranjang', 'order']],
            'payment' => ['Pemrosesan Pembayaran', ['payment', 'pembayaran', 'midtrans', 'stripe', 'paypal', 'invoice']],
            'transaction' => ['Riwayat Transaksi', ['transaction', 'transaksi', 'history', 'riwayat']],
        ];

        foreach ($matches as $key => [$label, $needles]) {
            foreach ($needles as $needle) {
                if (str_contains($lower, $needle)) {
                    return [
                        'key' => $key,
                        'label' => $label,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function groups(): array
    {
        if ($this->groups !== null) {
            return $this->groups;
        }

        if (function_exists('config')) {
            $configured = config('laravel-dfd.groups', config('dfd.groups', []));

            if (is_array($configured)) {
                return $configured;
            }
        }

        return [];
    }

    /**
     * @return array{key: string, label: string}
     */
    private function fallback(string $name): array
    {
        $base = class_basename(str_contains($name, '@') ? explode('@', $name, 2)[0] : $name);
        $base = preg_replace('/Controller$/', '', $base) ?: $base;
        $base = preg_replace('/(?<!^)[A-Z]/', ' $0', (string) $base) ?: $base;
        $base = trim((string) $base);

        if ($base === '' || str_contains($base, '\\')) {
            $base = 'Application';
        }

        return [
            'key' => $this->slug($base),
            'label' => 'Manajemen ' . $base,
        ];
    }

    private function titleFromKey(string $key): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $key));
    }

    private function slug(string $value): string
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', strtolower($value));

        return trim((string) $slug, '-') ?: 'application';
    }
}
