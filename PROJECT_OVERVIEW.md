# Laravel DFD Project Overview

## Ringkasan

Project ini adalah package Laravel untuk membantu membuat dokumentasi Data Flow Diagram (DFD) dari aplikasi Laravel.

Package ini membaca struktur aplikasi Laravel, seperti route, controller action, pemanggilan model Eloquent, dan penggunaan database. Data tersebut kemudian dinormalisasi menjadi struktur internal yang nantinya bisa dipakai untuk menghasilkan diagram DFD.

## Tujuan

Tujuan utama package ini adalah:

- Memindai route Laravel.
- Membaca source code PHP menggunakan AST.
- Mendeteksi proses aplikasi dari route dan controller action.
- Mendeteksi model Eloquent dan database table.
- Membentuk Intermediate Representation (IR) untuk DFD.
- Menjadi fondasi untuk generator diagram DFD.

## Komponen Utama

### Route Scanner

File:

- `src/Scanner/RouteScanner.php`

Fungsi:

- Membaca route dari Laravel `Route` facade.
- Mengambil URI route.
- Mengambil HTTP method.
- Mengambil controller action.

Contoh output:

```php
[
    [
        'uri' => 'users/{user}',
        'methods' => ['GET', 'HEAD'],
        'action' => 'App\Http\Controllers\UserController@show',
    ],
]
```

### AST Parser

File:

- `src/Parser/ASTParser.php`
- `src/Parser/ASTTraverser.php`

Fungsi:

- Menggunakan `nikic/php-parser`.
- Parse source PHP menjadi AST.
- Traverse node AST.
- Mendeteksi node penting seperti:
  - `StaticCall`
  - `MethodCall`
  - `Variable`
  - `Assign`
  - `FunctionCall`

### Model Scanner

File:

- `src/Scanner/ModelScanner.php`

Fungsi:

- Mendeteksi pemakaian model Eloquent.
- Mendeteksi pemakaian `DB` facade.
- Mendeteksi target datastore seperti nama model dan nama table.

Contoh yang dideteksi:

```php
User::create(['name' => 'A']);
Post::where('published', true)->update(['featured' => true]);
DB::table('users')->delete();
```

Contoh output:

```php
[
    'models' => ['User', 'Post'],
    'tables' => ['users'],
    'operations' => [
        [
            'type' => 'model_create',
            'target' => 'User',
        ],
    ],
]
```

### Intermediate Representation

Folder:

- `src/IR`

Class:

- `ProcessNode`
- `DataStoreNode`
- `ExternalEntityNode`
- `DataFlow`

Fungsi:

- Menyimpan struktur DFD dalam bentuk object sederhana.
- Mendukung `toArray()`.
- Mendukung JSON serialization.

Contoh:

```php
new ProcessNode(
    id: 'process.users.store',
    name: 'Store User',
    inputs: ['request'],
    outputs: ['user'],
);
```

## Testing

Project ini menggunakan:

- PHPUnit
- Orchestra Testbench

File penting:

- `phpunit.xml`
- `tests/TestCase.php`

Test dapat dijalankan dengan:

```bash
composer test
```

Atau langsung:

```bash
vendor/bin/phpunit
```

## Status Saat Ini

Yang sudah tersedia:

- Laravel package service provider.
- Route scanner.
- AST parser dan traverser.
- Model/datastore scanner.
- IR class untuk DFD.
- Unit test dengan Laravel application container via Orchestra Testbench.

Yang belum tersedia:

- Generator DFD final.
- Export ke format diagram seperti Mermaid, PlantUML, Graphviz, atau JSON schema khusus.
- Integrasi command `dfd:generate` dengan scanner dan IR.

## Gambaran Pipeline

Alur kerja yang dituju:

```text
Laravel Routes
    -> RouteScanner
    -> Controller / action discovery
    -> ASTParser
    -> ModelScanner
    -> IR Nodes and Data Flows
    -> DFD Generator
```

## Kesimpulan

Project ini adalah fondasi package Laravel untuk menghasilkan DFD secara otomatis. Fokus saat ini masih pada scanning dan normalisasi data dari aplikasi Laravel. Tahap berikutnya adalah menghubungkan scanner ke IR secara penuh, lalu membuat generator output diagram.
