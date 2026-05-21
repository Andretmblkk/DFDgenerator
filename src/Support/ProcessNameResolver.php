<?php

declare(strict_types=1);

namespace LaravelDfd\Support;

use LaravelDfd\IR\ProcessNode;

final class ProcessNameResolver
{
    public function level2Name(ProcessNode $process): string
    {
        $uri = $this->uri($process);
        $method = $this->method($process);
        $context = strtolower($process->getName() . ' ' . $uri . ' ' . implode(' ', $process->getOutputs()));
        $resource = $this->resourceName($uri, $process);

        if (str_contains($context, 'login')) {
            return 'Login User';
        }

        if (str_contains($context, 'checkout')) {
            return 'Checkout Produk';
        }

        if (str_contains($context, 'payment') || str_contains($context, 'pembayaran')) {
            return 'Proses Pembayaran';
        }

        if (str_contains($context, 'transaction') || str_contains($context, 'transaksi')) {
            return str_contains($method, 'GET') || str_contains($method, 'HEAD') ? 'Lihat Riwayat Transaksi' : 'Kelola Transaksi';
        }

        if (str_contains($context, 'product') || str_contains($context, 'produk')) {
            return str_contains($method, 'GET') || str_contains($method, 'HEAD') ? 'Lihat Daftar Produk' : 'Kelola Produk';
        }

        return match ($this->action($process)) {
            'index' => 'Lihat Daftar ' . $resource,
            'show' => 'Lihat Detail ' . $resource,
            'create' => 'Buka Form ' . $resource,
            'store' => 'Simpan ' . $resource,
            'edit' => 'Ubah ' . $resource,
            'update' => 'Perbarui ' . $resource,
            'destroy', 'delete' => 'Hapus ' . $resource,
            default => $this->businessFromController($process, $resource),
        };
    }

    /**
     * @return array<int, string>
     */
    public function level2Steps(ProcessNode $process, string $groupLabel): array
    {
        $context = strtolower($groupLabel . ' ' . $process->getName() . ' ' . implode(' ', $process->getInputs()) . ' ' . implode(' ', $process->getOutputs()));

        if (str_contains($context, 'checkout')) {
            return [
                'Input Checkout',
                'Validasi Checkout',
                'Cek Produk dan Stok',
                'Hitung Total',
                'Simpan Transaksi',
                'Proses Pembayaran',
                'Update Status',
                'Kirim Informasi Checkout',
            ];
        }

        if (str_contains($context, 'payment') || str_contains($context, 'pembayaran') || str_contains($context, 'midtrans') || str_contains($context, 'stripe')) {
            return [
                'Terima Data Pembayaran',
                'Validasi Transaksi',
                'Request Payment Gateway',
                'Terima Status Pembayaran',
                'Update Transaksi',
                'Kirim Status Pembayaran',
            ];
        }

        if (str_contains($context, 'login') || str_contains($context, 'autentikasi')) {
            return [
                'Input Data Login',
                'Validasi Login',
                'Buat Sesi User',
                'Kirim Status Login',
            ];
        }

        if (str_contains($context, 'product') || str_contains($context, 'produk')) {
            return [
                'Ambil Data Produk',
                'Susun Informasi Produk',
                'Tampilkan Daftar Produk',
            ];
        }

        if (str_contains($context, 'transaction') || str_contains($context, 'transaksi') || str_contains($context, 'riwayat')) {
            return [
                'Ambil Data Transaksi',
                'Susun Detail Transaksi',
                'Tampilkan Riwayat Transaksi',
            ];
        }

        $steps = [];

        if (str_contains($context, 'validate')) {
            $steps[] = 'Validasi Data';
        }

        foreach ($process->getOutputs() as $output) {
            $operation = $this->operationName($output);

            if ($operation !== null) {
                $steps[] = $operation;
            }
        }

        $steps[] = $this->level2Name($process);

        return array_values(array_unique($steps));
    }

    public function flowLabel(string $text): string
    {
        $lower = strtolower($text);

        if (str_contains($lower, 'login') || str_contains($lower, 'credential')) {
            return 'Data Login';
        }

        if (str_contains($lower, 'produk') || str_contains($lower, 'product') || str_contains($lower, 'stok')) {
            return 'Data Produk';
        }

        if (str_contains($lower, 'checkout')) {
            return 'Data Checkout';
        }

        if (str_contains($lower, 'payment') || str_contains($lower, 'pembayaran')) {
            return str_contains($lower, 'status') ? 'Status Pembayaran' : 'Data Pembayaran';
        }

        if (str_contains($lower, 'transaksi') || str_contains($lower, 'transaction')) {
            return 'Data Transaksi';
        }

        if (str_contains($lower, 'riwayat')) {
            return 'Riwayat Transaksi';
        }

        if (str_contains($lower, 'response') || str_contains($lower, 'informasi')) {
            return 'Informasi Sistem';
        }

        return 'Data Bisnis';
    }

    /**
     * @return array<int, string>
     */
    public function level3Names(ProcessNode $process): array
    {
        $context = strtolower($process->getName() . ' ' . implode(' ', $process->getInputs()) . ' ' . implode(' ', $process->getOutputs()));

        if (str_contains($context, 'checkout')) {
            return [
                'Terima Data Checkout',
                'Validasi User dan Produk',
                'Cek Stok Produk',
                'Hitung Total Belanja',
                'Buat Transaksi',
                'Simpan Item Transaksi',
                'Kurangi Stok Produk',
                'Siapkan Pembayaran',
                'Kirim Response Checkout',
            ];
        }

        if (str_contains($context, 'payment') || str_contains($context, 'midtrans') || str_contains($context, 'stripe')) {
            return [
                'Terima Data Pembayaran',
                'Validasi Transaksi',
                'Simpan Log Pembayaran',
                'Kirim Request ke Payment Gateway',
                'Terima Status Pembayaran',
                'Update Status Transaksi',
                'Kirim Response Pembayaran',
            ];
        }

        if (str_contains($context, 'login')) {
            return [
                'Terima Credential Login',
                'Validasi Credential',
                'Buat Sesi User',
                'Kirim Status Login',
            ];
        }

        $names = [];

        if (str_contains($context, 'validate')) {
            $names[] = 'Validasi Data';
        }

        foreach ($process->getOutputs() as $output) {
            $operation = $this->operationName($output);

            if ($operation !== null) {
                $names[] = $operation;
            }
        }

        $names[] = 'Kirim Response';

        return array_values(array_unique($names));
    }

    public function shouldDecomposeToLevel3(ProcessNode $process): bool
    {
        $context = strtolower($process->getName() . ' ' . implode(' ', $process->getInputs()) . ' ' . implode(' ', $process->getOutputs()));

        return str_contains($context, 'checkout')
            || str_contains($context, 'payment')
            || str_contains($context, 'pembayaran')
            || str_contains($context, 'midtrans')
            || str_contains($context, 'stripe')
            || str_contains($context, 'transaction')
            || str_contains($context, 'transaksi');
    }

    private function operationName(string $output): ?string
    {
        $lower = strtolower($output);

        if (str_contains($lower, 'response') || str_contains($lower, 'request') || str_contains($lower, 'str.') || str_contains($lower, 'artisan')) {
            return null;
        }

        if (str_contains($lower, 'where') || str_contains($lower, 'find') || str_contains($lower, 'get')) {
            return 'Ambil Data';
        }

        if (str_contains($lower, 'create') || str_contains($lower, 'insert') || str_contains($lower, 'save')) {
            return 'Simpan Data';
        }

        if (str_contains($lower, 'update')) {
            return 'Perbarui Data';
        }

        if (str_contains($lower, 'delete') || str_contains($lower, 'destroy')) {
            return 'Hapus Data';
        }

        if (str_contains($lower, 'validate')) {
            return 'Validasi Data';
        }

        if (str_contains($lower, 'http.post') || str_contains($lower, 'curl')) {
            return 'Kirim Request Eksternal';
        }

        return null;
    }

    private function method(ProcessNode $process): string
    {
        $input = $process->getInputs()[0] ?? '';
        $parts = preg_split('/\s+/', $input) ?: [];

        return strtoupper((string) ($parts[0] ?? 'GET'));
    }

    private function uri(ProcessNode $process): string
    {
        $input = $process->getInputs()[0] ?? '';
        $parts = preg_split('/\s+/', $input, 2) ?: [];

        return strtolower(trim((string) ($parts[1] ?? ''), '/'));
    }

    private function action(ProcessNode $process): string
    {
        $name = $process->getName();

        if (str_contains($name, '@')) {
            return strtolower(explode('@', $name, 2)[1]);
        }

        return strtolower(class_basename($name));
    }

    private function resourceName(string $uri, ProcessNode $process): string
    {
        $segment = trim((string) strtok($uri, '/'), '{}');

        if ($segment === '') {
            $segment = class_basename(str_contains($process->getName(), '@') ? explode('@', $process->getName(), 2)[0] : $process->getName());
            $segment = preg_replace('/Controller$/', '', $segment) ?: $segment;
        }

        $map = [
            'users' => 'User',
            'login' => 'User',
            'products' => 'Produk',
            'product' => 'Produk',
            'checkout' => 'Checkout',
            'payments' => 'Pembayaran',
            'payment' => 'Pembayaran',
            'transactions' => 'Transaksi',
            'transaction' => 'Transaksi',
            'orders' => 'Pesanan',
            'cart' => 'Keranjang',
            'carts' => 'Keranjang',
        ];

        return $map[$segment] ?? ucwords(str_replace(['-', '_'], ' ', rtrim($segment, 's')));
    }

    private function businessFromController(ProcessNode $process, string $resource): string
    {
        $controller = class_basename(str_contains($process->getName(), '@') ? explode('@', $process->getName(), 2)[0] : $process->getName());
        $controller = preg_replace('/Controller$/', '', $controller) ?: $controller;
        $controller = trim((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $controller));

        if ($controller !== '') {
            return 'Kelola ' . $controller;
        }

        return 'Kelola ' . $resource;
    }
}
