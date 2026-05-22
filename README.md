# Laravel DFD Generator

Laravel DFD Generator adalah package Laravel untuk membuat Data Flow Diagram (DFD) otomatis dari aplikasi Laravel. Package ini membaca route, controller action, pemakaian model Eloquent, dan akses database, lalu menampilkan hasilnya lewat live web viewer `/dfd` atau export static HTML/SVG/JSON/Mermaid.

Project ini dibuat oleh Andre Tumbelaka.

## Fitur

- Auto-scan route Laravel dan controller action.
- Parse source PHP memakai `nikic/php-parser`.
- Deteksi proses bisnis dari route/controller.
- Deteksi model Eloquent dan table database.
- Generate DFD Level 0 sampai Level 3.
- Live web viewer modern di `/dfd` tanpa sambung JSON manual.
- Dark UI, sidebar hierarchy, toolbar zoom/pan, minimap, dan export.
- CSS/JS viewer diload otomatis lewat route asset package.
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

Laravel akan auto-discover service provider package ini. Setelah install, route `/dfd` langsung tersedia selama config route package aktif.

Kalau auto-discovery dimatikan, daftarkan provider manual di `config/app.php`:

```php
'providers' => [
    LaravelDfd\LaravelDfdServiceProvider::class,
],
```

## Update package

Kalau package sudah pernah terinstall dan ingin mengambil versi terbaru:

```bash
composer update andretmblkk/dfdgenerator
```

## Publish config

Publish config bersifat opsional. Jalankan ini hanya kalau ingin mengubah prefix route, middleware, output path, semantic groups, atau setting lain.

```bash
php artisan vendor:publish --tag=dfd-config
```

File yang dibuat:

```text
config/laravel-dfd.php
config/dfd.php
```

## Publish assets

Live viewer `/dfd` langsung bekerja setelah install karena package menyajikan CSS/JS lewat route asset internal:

```text
/dfd/assets/styles.css
/dfd/assets/viewer.js
```

Artinya user tidak wajib menjalankan `vendor:publish` agar UI tampil modern. Kalau ingin menyalin asset package ke public path Laravel standar, jalankan:

```bash
php artisan vendor:publish --tag=dfd-assets
```

File akan dipublish ke:

```text
public/vendor/dfdgenerator
```

## Usage: live viewer

Jalankan aplikasi Laravel:

```bash
php artisan serve
```

Buka DFD viewer:

```text
http://localhost:8000/dfd
```

Package akan scan route/controller aplikasi saat halaman `/dfd` dibuka, lalu render viewer langsung. Tidak perlu generate JSON atau menghubungkan frontend manual.

Viewer route mode memakai asset URL absolut dari Laravel `asset()`, sehingga `/dfd` tetap benar meskipun dibuka tanpa trailing slash. CSS dan JS akan diload dari `/dfd/assets/...`, bukan dari `/assets/...`.

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

Kalau prefix diubah, asset viewer ikut menyesuaikan. Contoh:

```env
DFD_ROUTE_PREFIX=developer/dfd
```

Viewer:

```text
http://localhost:8000/developer/dfd
```

Asset:

```text
http://localhost:8000/developer/dfd/assets/styles.css
http://localhost:8000/developer/dfd/assets/viewer.js
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

Static HTML mode memakai asset relatif:

```html
<link rel="stylesheet" href="assets/styles.css">
<script src="assets/viewer.js"></script>
```

Karena itu `storage/dfd/index.html` bisa dibuka sebagai file static selama folder `assets` tetap berada di sebelah `index.html`.

Custom output folder:

```bash
php artisan dfd:generate --output=public/dfd
```

Buka:

```text
public/dfd/index.html
```

Kalau output diarahkan ke `public/dfd`, viewer static juga bisa dibuka lewat web server:

```text
http://localhost:8000/dfd/index.html
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
6. Viewer route mode menampilkan diagram langsung di `/dfd`.
7. Static mode menulis `index.html`, SVG, JSON, dan asset viewer ke output folder.

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
public/
  assets/
    styles.css
    viewer.js
resources/
  views/
tests/
  Fixtures/
  Unit/
composer.json
phpunit.xml
README.md
```

## Catatan penting

Package ini menganalisis struktur aplikasi berdasarkan route, controller, model, dan pemakaian database yang bisa dibaca secara statis. Kalau logic aplikasi terlalu dinamis, misalnya route/controller dibuat saat runtime, hasil diagram bisa kurang lengkap.

Untuk live viewer, pastikan route package tidak diblokir middleware aplikasi. Secara default package memakai middleware `web`.

## License

MIT
