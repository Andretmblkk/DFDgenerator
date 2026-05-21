# Laravel DFD Generator

Laravel DFD Generator adalah package Laravel untuk membuat Data Flow Diagram (DFD) otomatis dari aplikasi Laravel. Package ini membaca route, controller action, pemakaian model Eloquent, akses database, lalu menampilkan DFD lewat web viewer `/dfd` atau export static HTML/SVG/JSON/Mermaid.

Project ini dibuat oleh Andre Tumbelaka.

## Fitur

- Auto-scan route Laravel dan controller action.
- Parse source PHP memakai `nikic/php-parser`.
- Deteksi proses bisnis dari route/controller.
- Deteksi model Eloquent dan table database.
- Generate DFD Level 0 sampai Level 3.
- Live web viewer di `/dfd` tanpa sambung JSON manual.
- Export static HTML viewer, SVG, JSON, dan Mermaid.
- Artisan command: `php artisan dfd:generate`.

## Requirement

- PHP 8.2 atau lebih baru.
- Laravel 10, 11, atau 12.
- Composer.

## Installation

Install langsung lewat Composer:

```bash
composer require andretmblkk/dfdgenerator
```

Laravel akan auto-discover service provider package ini. Setelah package tersedia di Packagist, user tidak perlu menambahkan `repositories` manual ke `composer.json`. Ini flow yang benar, bukan ritual tempel-tempel JSON yang bikin capek.

Kalau auto-discovery dimatikan, daftarkan provider manual di `config/app.php`:

```php
'providers' => [
    LaravelDfd\LaravelDfdServiceProvider::class,
],
```

## Publish config

```bash
php artisan vendor:publish --tag=dfd-config
```

File yang dibuat:

```text
config/laravel-dfd.php
config/dfd.php
```

## Usage: live viewer

Jalankan Laravel app:

```bash
php artisan serve
```

Buka DFD viewer:

```text
http://localhost:8000/dfd
```

Nah ini flow yang benar. Tidak perlu generate JSON lalu sambung manual ke frontend. Package akan scan route/controller aplikasi saat halaman `/dfd` dibuka, lalu render viewer langsung.

## Konfigurasi route viewer

Default route:

```php
'route' => [
    'enabled' => env('DFD_ROUTE_ENABLED', true),
    'prefix' => env('DFD_ROUTE_PREFIX', 'dfd'),
    'middleware' => ['web'],
],
```

Ubah prefix lewat `.env`:

```env
DFD_ROUTE_PREFIX=dfd
```

Kalau ingin disable viewer:

```env
DFD_ROUTE_ENABLED=false
```

## Konfigurasi nama sistem

Tambahkan ke `.env` aplikasi Laravel:

```env
DFD_SYSTEM_NAME="Nama Sistem Saya"
```

## Export static HTML/SVG/JSON

Kalau tetap butuh file static:

```bash
php artisan dfd:generate
```

Default output:

```text
storage/dfd
```

File yang dihasilkan:

```text
storage/dfd/index.html
storage/dfd/level-0.svg
storage/dfd/level-0.json
storage/dfd/level-1.svg
storage/dfd/level-1.json
storage/dfd/level-2.svg
storage/dfd/level-2.json
storage/dfd/level-3.svg
storage/dfd/level-3.json
storage/dfd/assets/styles.css
storage/dfd/assets/viewer.js
```

Custom output folder:

```bash
php artisan dfd:generate --output=public/dfd
```

Buka:

```text
public/dfd/index.html
```

## Export Mermaid legacy

```bash
php artisan dfd:generate --format=mermaid --output=storage/dfd/diagram.mmd
```

Export JSON legacy:

```bash
php artisan dfd:generate --format=mermaid --json --output=storage/dfd/diagram.json
```

## Debug

Kalau command gagal dan butuh detail error:

```bash
php artisan dfd:generate --debug
```

## Cara kerja singkat

1. Package membaca route Laravel.
2. Action controller diparse dari source code.
3. Pemakaian model/table dideteksi.
4. Proses bisnis dikelompokkan berdasarkan config semantic groups.
5. DFD Level 0 sampai Level 3 dibangun.
6. Viewer `/dfd` menampilkan diagram langsung.

## Development package

Clone repository:

```bash
git clone https://github.com/Andretmblkk/DFDgenerator.git
cd DFDgenerator
```

Install dependency:

```bash
composer install
```

Jalankan test:

```bash
composer test
```

Atau langsung:

```bash
vendor/bin/phpunit
```

Regenerate autoload setelah mengubah class:

```bash
composer dump-autoload
```

## Struktur project

```text
config/
  dfd.php
  laravel-dfd.php
routes/
  web.php
src/
  Builder/
  Commands/
  Generator/
  Http/
  IR/
  Parser/
  Renderer/
  Scanner/
  Support/
tests/
  Fixtures/
  Unit/
composer.json
phpunit.xml
README.md
```

## Catatan penting

Package ini menganalisis struktur aplikasi berdasarkan route, controller, model, dan pemakaian database yang bisa dibaca secara statis. Kalau logic aplikasi terlalu dinamis, misalnya route/controller dibuat runtime secara ajaib, hasil diagram bisa kurang lengkap. Ya namanya static analysis, bukan dukun santet.

## License

MIT
