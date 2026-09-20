# Dependency Handoff

Dokumen ini mencatat dependency dan runtime yang terdeteksi pada server saat project dipindahkan untuk dilanjutkan di local development.

Tanggal pemeriksaan: 2026-09-20  
Commit saat pemeriksaan: `4c65118be5ab93e5d12c4b3b3684a50a2283efd0` (`main`)

> Jangan menyalin file `.env` dari server ke repository atau ke komputer lain. Buat `.env` baru secara lokal dan isi credential secara terpisah.

## Ringkasan stack

| Komponen | Versi/konfigurasi terdeteksi |
| --- | --- |
| OS | Ubuntu 22.04 LTS |
| PHP CLI | 8.3.33 |
| Composer | 2.10.2 |
| Node.js | 20.20.2 |
| npm | 10.8.2 |
| Web server | Apache 2.4.52 |
| Database server | MySQL 8.0.46 dan PostgreSQL 14.24 terpasang |
| Framework | Laravel 13.31.0 (terkunci di `composer.lock`) |
| Frontend | Vue 3.5.42, Inertia Vue 3.7.0, Vite 6.4.3 |
| Package lock | `composer.lock`, `package-lock.json` (`lockfileVersion: 3`) |

Project mensyaratkan PHP `^8.3`. Walaupun PHP-FPM 8.1 dan 8.3 sama-sama terpasang di server, local development harus menggunakan PHP 8.3 atau versi kompatibel.

## PHP / Composer

### Dependency production

| Package | Constraint | Versi terkunci |
| --- | --- | --- |
| `laravel/framework` | `^13.8` | `v13.31.0` |
| `barryvdh/laravel-dompdf` | `^3.1` | `v3.1.2` |
| `inertiajs/inertia-laravel` | `^3.1` | `v3.3.3` |
| `laravel/tinker` | `^3.0` | `v3.0.2` |
| `openspout/openspout` | `^5.3` | `v5.3.0` |

### Dependency development/test

| Package | Constraint | Versi terkunci |
| --- | --- | --- |
| `fakerphp/faker` | `^1.23` | `v1.24.1` |
| `laravel/pail` | `^1.2.5` | `v1.2.7` |
| `laravel/pao` | `^1.0.6` | `v1.1.5` |
| `laravel/pint` | `^1.27` | `v1.32.1` |
| `mockery/mockery` | `^1.6` | `1.6.15` |
| `nunomaduro/collision` | `^8.6` | `v8.9.5` |
| `phpunit/phpunit` | `^12.5.12` | `12.5.35` |

Extension PHP yang terdeteksi dan relevan: `ctype`, `curl`, `dom`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`, `session`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, dan `zip`.

## JavaScript / npm

### Dependency aplikasi

| Package | Constraint | Versi terkunci |
| --- | --- | --- |
| `vue` | `^3.5.39` | `3.5.42` |
| `@inertiajs/vue3` | `^3.6.1` | `3.7.0` |
| `@vitejs/plugin-vue` | `^5.2.4` | `5.2.4` |
| `bootstrap` | `^5.3.8` | `5.3.8` |
| `bootstrap-icons` | `^1.13.1` | `1.13.1` |
| `chart.js` | `^4.5.1` | `4.5.1` |

### Dependency development/build

| Package | Constraint | Versi terkunci |
| --- | --- | --- |
| `vite` | `^6.4.3` | `6.4.3` |
| `typescript` | `~5.9.3` | `5.9.3` |
| `vue-tsc` | `~3.3.11` | `3.3.11` |
| `laravel-vite-plugin` | `^1.3.0` | `1.3.0` |
| `@playwright/test` | `^1.63.0` | `1.63.0` |
| `@types/node` | `^20.19.43` | `20.19.43` |
| `concurrently` | `^9.0.1` | `9.2.4` |

Script penting:

```text
npm run dev       # Vite development server; jalankan di local saja
npm run build     # build asset production
npm run typecheck # pemeriksaan TypeScript/Vue
npm run test:browser
```

`vite.config.js` memakai entry berikut:

```text
resources/css/app.css
resources/js/inertia.ts
```

## Database dan service

Project mendukung konfigurasi database Laravel untuk SQLite, MySQL, dan PostgreSQL. Server memiliki MySQL dan PostgreSQL, tetapi nilai koneksi aktual sengaja tidak dicatat di dokumen ini. Gunakan nilai lokal di `.env` sesuai database yang dipilih.

Service server yang terdeteksi:

```text
apache2.service
php8.3-fpm.service
mysql.service
postgresql@14-main.service
```

Untuk local development, SQLite adalah pilihan paling sederhana bila dataset production tidak diperlukan. Untuk pengujian yang menyerupai server, gunakan MySQL dan import database dari backup yang sudah disanitasi.

## Setup local yang disarankan

Prasyarat:

```text
PHP 8.3+
Composer 2+
Node.js 20+
npm 10+
SQLite atau MySQL/PostgreSQL
```

Langkah awal:

```bash
git clone <repository-url>
cd demo-lms.didzacorp.com

cp .env.example .env
composer install
php artisan key:generate
npm ci

# Sesuaikan DB_* di .env, lalu:
php artisan migrate
npm run build
```

Saat aktif development:

```bash
composer run dev
```

Perintah tersebut menjalankan server Laravel, queue listener, log viewer, dan Vite secara bersamaan. Jalankan hanya di komputer local karena watcher TypeScript/Vite cukup boros RAM di server 3,8 GB.

Sebelum commit perubahan:

```bash
npm run typecheck
composer run test
composer run lint
```

## Deployment production

Server production tidak perlu menjalankan `npm run dev`. Build asset dilakukan sekali, lalu web server menyajikan hasil build:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
```

Catatan:

- Jangan menjalankan `composer run dev` di production.
- Jangan menjalankan `npm run dev` atau watcher TypeScript di production.
- Jangan menjalankan script `composer setup` secara buta di production karena script tersebut dapat membuat `.env`, generate key, menjalankan migration, dan memasang dependency npm.
- Pastikan virtual host Apache memakai PHP-FPM 8.3, bukan PHP-FPM 8.1.
- Simpan backup database dan `.env` di luar repository.

## Catatan kondisi server saat pemeriksaan

Pada saat pemeriksaan, proses VS Code Remote/TypeScript/Volar mengonsumsi RAM besar. Tidak ditemukan proses aktif `npm run dev` atau `vite`; penggunaan RAM tersebut berasal dari language server dan extension host. Karena itu local development sebaiknya dipindahkan ke workstation lokal, sedangkan server digunakan untuk runtime production dan deployment build.

