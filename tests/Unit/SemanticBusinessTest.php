<?php

declare(strict_types=1);

namespace LaravelDfd\Tests\Unit;

use Illuminate\Support\Facades\Route;
use LaravelDfd\Builder\DFDBuilder;
use LaravelDfd\Builder\HierarchyBuilder;
use LaravelDfd\IR\ProcessNode;
use LaravelDfd\Support\ProcessNameResolver;
use LaravelDfd\Support\SemanticClassifier;
use LaravelDfd\Tests\TestCase;

final class SemanticBusinessTest extends TestCase
{
    public function test_process_name_resolver_generates_business_oriented_labels(): void
    {
        $resolver = new ProcessNameResolver();

        self::assertSame('Login User', $resolver->level2Name(new ProcessNode(
            'process.login',
            'AuthController@login',
            ['POST login'],
        )));

        self::assertSame('Lihat Daftar Produk', $resolver->level2Name(new ProcessNode(
            'process.products',
            'ProductController@index',
            ['GET|HEAD products'],
        )));

        self::assertSame('Checkout Produk', $resolver->level2Name(new ProcessNode(
            'process.checkout',
            'CheckoutController@store',
            ['POST checkout'],
        )));
    }

    public function test_semantic_classifier_groups_ecommerce_business_flows(): void
    {
        $classifier = new SemanticClassifier([]);

        self::assertSame([
            'key' => 'payment',
            'label' => 'Pemrosesan Pembayaran',
        ], $classifier->classify(new ProcessNode(
            'process.payment',
            'PaymentService@process',
            ['POST payments'],
            ['Http.post'],
        )));
    }

    public function test_builder_filters_internal_routes_and_adds_payment_gateway_entity(): void
    {
        Route::get('_ignition/health-check', SemanticDebugController::class . '@index');
        Route::post('payments', SemanticPaymentController::class . '@store');

        $ir = (new DFDBuilder())->build();

        self::assertCount(1, $ir['processes']);
        self::assertSame('Payment Gateway', $ir['externalEntities'][1]->getName());
    }

    public function test_level_two_uses_business_sequence_without_http_labels(): void
    {
        Route::post('checkout', SemanticCheckoutController::class . '@store');

        $hierarchy = (new HierarchyBuilder())->build(3);
        $level2 = array_values(array_filter(
            $hierarchy['levels'],
            static fn ($level): bool => $level->getLevel() === 2 && in_array('Input Checkout', array_map(
                static fn ($process): string => $process->getName(),
                $level->getProcesses(),
            ), true),
        ));

        self::assertNotEmpty($level2);
        $level2 = $level2[0];

        $labels = array_map(static fn ($process): string => $process->getName(), $level2->getProcesses());

        self::assertContains('Input Checkout', $labels);
        self::assertContains('Validasi Checkout', $labels);
        self::assertContains('Cek Produk dan Stok', $labels);
        self::assertContains('Proses Pembayaran', $labels);

        foreach ($level2->getFlows() as $flow) {
            self::assertDoesNotMatchRegularExpression('/\b(GET|POST|PUT|PATCH|DELETE|HEAD)\b/', $flow->getLabel());
        }
    }
}

final class SemanticDebugController
{
    public function index(): void
    {
    }
}

final class SemanticPaymentController
{
    public function store(): void
    {
        Http::post('https://payment.example.test', []);
    }
}

final class SemanticCheckoutController
{
    public function store(): void
    {
        Product::where('stock', '>', 0)->get();
        Transaction::create(['total' => 100]);
        PaymentLog::create(['status' => 'pending']);
        Http::post('https://payment.example.test', []);
    }
}
