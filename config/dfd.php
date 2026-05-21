<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Output Path
    |--------------------------------------------------------------------------
    |
    | This path will be used by future generators when writing data flow
    | documentation artifacts.
    |
    */

    'system_name' => env('DFD_SYSTEM_NAME', env('APP_NAME', 'Laravel Application') . ' System'),

    'output_path' => storage_path('dfd'),

    'groups' => (require __DIR__ . '/laravel-dfd.php')['groups'],
];
