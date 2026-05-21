# Laravel DFD Generator

Laravel DFD Generator adalah package Laravel untuk membuat dokumentasi Data Flow Diagram (DFD) otomatis dari aplikasi Laravel. Package ini membaca route, controller action, pemakaian model Eloquent, akses database, lalu menghasilkan dokumentasi DFD dalam bentuk HTML viewer, SVG, JSON, atau Mermaid.

Project ini dibuat oleh Andre Tumbelaka.

## Fitur

- Scan route Laravel dan controller action.
- Parse source PHP memakai `nikic/php-parser`.
- Deteksi proses bisnis dari route/controller.
- Deteksi model Eloquent dan table database.
- Bentuk Intermediate Representation (IR) untuk DFD.
- Generate DFD Level 0 sampai Level 3.
- Export HTML viewer interaktif.
- Export SVG dan JSON per level.
- Export Mermaid legacy.
- Integrasi Artisan command: `php artisan dfd:generate`.

## Requirement

- PHP 8.2 atau lebih baru.
- Laravel 10, 11, atau 12.
- Composer.

## Instalasi dari GitHub

Karena package ini belum dipublish ke Packagist, install lewat repository GitHub.

Tambahkan repository ke `composer.json` project Laravel yang mau dianalisis:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/Andretmblkk/DFDgenerator.git"
    }
  ]
}
```

Lalu install package:

```bash
composer require laravel-dfd/laravel-dfd:dev-master
```

Laravel akan auto-discover service provider package ini.

Kalau auto-discovery dimatikan, daftarkan provider secara manual di `config/app.php`:

```php
'providers' => [
    LaravelDfd\LaravelDfdServiceProvider::class,
],
```

## Publish konfigurasi

Jalankan:

```bash
php artisan vendor:publish --tag=dfd-config
```

File konfigurasi yang akan dibuat:

```text
config/dfd.php
config/laravel-dfd.php
```

Konfigurasi utama:

```php
'system_name' => env('DFD_SYSTEM_NAME', env('APP_NAME', 'Laravel Application') . ' System'),
'output_path' => storage_path('dfd'),
```

Kalau mau ganti nama sistem di diagram, tambahkan ke `.env` aplikasi Laravel:

```env
DFD_SYSTEM_NAME="Nama Sistem Saya"
```

## Cara pakai

Generate dokumentasi DFD lengkap:

```bash
php artisan dfd:generate
```

Default output akan masuk ke:

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

Buka hasilnya di browser:

```text
storage/dfd/index.html
```

## Custom output folder

Kalau mau output ke folder lain:

```bash
php artisan dfd:generate --output=public/dfd
```

Lalu buka:

```text
public/dfd/index.html
```

## Export Mermaid

Untuk generate Mermaid diagram legacy:

```bash
php artisan dfd:generate --format=mermaid --output=storage/dfd/diagram.mmd
```

Untuk export JSON legacy:

```bash
php artisan dfd:generate --format=mermaid --json --output=storage/dfd/diagram.json
```

## Debug

Kalau command gagal dan butuh detail error:

```bash
php artisan dfd:generate --debug
```

## Contoh workflow pemakaian

1. Install package ke project Laravel.
2. Publish config.
3. Pastikan route dan controller aplikasi sudah terbaca Laravel.
4. Jalankan `php artisan dfd:generate`.
5. Buka `storage/dfd/index.html`.
6. Review diagram Level 0 sampai Level 3.

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
src/
  Builder/
  Commands/
  Generator/
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

Package ini menganalisis struktur aplikasi berdasarkan route, controller, model, dan pemakaian database yang bisa dibaca secara statis. Kalau logic aplikasi terlalu dinamis, misalnya route/controller dibuat runtime secara ajaib, hasil diagram bisa kurang lengkap. Ya namanya juga static analysis, bukan dukun.

## License

MIT
