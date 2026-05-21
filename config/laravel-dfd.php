<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | System Name
    |--------------------------------------------------------------------------
    |
    | Used as the single process label in the Level 0 context diagram.
    |
    */

    'system_name' => env('DFD_SYSTEM_NAME', env('APP_NAME', 'Laravel Application') . ' System'),

    /*
    |--------------------------------------------------------------------------
    | Output Path
    |--------------------------------------------------------------------------
    |
    | The formal DFD viewer writes diagram.json, diagram.svg, and index.html
    | into this directory when no --output directory is provided.
    |
    */

    'output_path' => storage_path('dfd'),

    /*
    |--------------------------------------------------------------------------
    | Semantic Process Groups
    |--------------------------------------------------------------------------
    |
    | Controllers, services, and keywords listed here are grouped into formal
    | Level 1 business processes.
    |
    */

    'groups' => [
        'authentication' => [
            'label' => 'Autentikasi',
            'controllers' => [
                'AuthController',
                'LoginController',
                'RegisterController',
            ],
            'keywords' => [
                'auth',
                'login',
                'register',
            ],
        ],

        'product' => [
            'label' => 'Manajemen Produk',
            'controllers' => [
                'ProductController',
                'CategoryController',
            ],
            'keywords' => [
                'product',
                'produk',
                'catalog',
            ],
        ],

        'checkout' => [
            'label' => 'Checkout Produk',
            'controllers' => [
                'CheckoutController',
                'CartController',
                'OrderController',
            ],
            'keywords' => [
                'checkout',
                'cart',
                'order',
            ],
        ],

        'payment' => [
            'label' => 'Pemrosesan Pembayaran',
            'controllers' => [
                'PaymentController',
            ],
            'services' => [
                'PaymentService',
            ],
            'keywords' => [
                'payment',
                'pembayaran',
                'invoice',
                'transaction',
            ],
        ],

        'transaction' => [
            'label' => 'Riwayat Transaksi',
            'controllers' => [
                'TransactionController',
                'HistoryController',
            ],
            'keywords' => [
                'transaction',
                'transaksi',
                'history',
                'riwayat',
            ],
        ],
    ],
];
