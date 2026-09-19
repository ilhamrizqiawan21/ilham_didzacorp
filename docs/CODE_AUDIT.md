# CODE_AUDIT.md

Audit kode ringkas LMS Sekolah.

Tanggal audit: 2026-07-09

## Ringkasan

Project sudah mengikuti struktur Laravel umum dengan pembagian role yang jelas. Perbaikan utama audit ini adalah merapikan penempatan logic import siswa ke service, memperbaiki pola temporary file export Excel, dan melengkapi dokumentasi yang sebelumnya belum sinkron.

## Temuan dan Status

### 1. Temporary File Excel Berisiko Permission Error

Status: diperbaiki.

Sebelumnya beberapa export Excel menulis ke:

```text
storage/app/temp
```

Pada server tertentu folder ini bisa tidak writable oleh web server dan memicu error 500. Pola export sekarang memakai:

```php
tempnam(sys_get_temp_dir(), 'prefix_')
```

File dikirim dengan `deleteFileAfterSend(true)`.

File terkait:

```text
app/Http/Controllers/ExportController.php
app/Services/SiswaTemplateService.php
```

### 2. Import Siswa Terlalu Banyak Logic di Controller

Status: diperbaiki.

Logic parsing, validasi, dan insert import siswa dipindah ke:

```text
app/Services/SiswaImportService.php
app/Services/SiswaTemplateService.php
```

Controller kembali fokus pada request validation dan response.

### 3. Dokumentasi README Menautkan File yang Belum Ada

Status: diperbaiki.

README menautkan `docs/SECURITY_CHECK_RESULT.md`, tetapi file tersebut belum ada. File audit keamanan sudah ditambahkan.

### 4. Test Runner Tidak Tersedia di Vendor Saat Ini

Status: perlu tindakan environment.

`php artisan test` tidak tersedia, dan `vendor/bin/phpunit` tidak ada pada instalasi vendor saat audit. Kemungkinan dependency dev tidak terinstall di server.

Rekomendasi:

```bash
composer install
composer test
```

Untuk production, dependency dev memang boleh tidak ada. Jalankan test di environment staging/local.

### 5. Route File Mulai Panjang

Status: rekomendasi bertahap.

`routes/web.php` sudah cukup panjang karena memuat semua role. Jika pertumbuhan fitur berlanjut, pecah menjadi:

```text
routes/admin.php
routes/guru.php
routes/siswa.php
routes/kepsek.php
```

Lalu load dari `bootstrap/app.php` atau service provider sesuai versi Laravel yang dipakai.

### 6. Controller Laporan dan Export Masih Padat

Status: rekomendasi bertahap.

`ExportController` masih memuat query, transformasi data, dan render file dalam satu controller. Untuk pengembangan berikutnya, pertimbangkan service per laporan:

```text
app/Services/Reports/NilaiReportService.php
app/Services/Reports/AbsensiReportService.php
app/Services/Reports/TugasReportService.php
```

## Standar Penempatan Baru

Lihat [ARCHITECTURE.md](ARCHITECTURE.md) untuk aturan penempatan kode.

## Dokumen Terkait

- [ARCHITECTURE.md](ARCHITECTURE.md)
- [IMPORT_SISWA.md](IMPORT_SISWA.md)
- [SECURITY_CHECK_RESULT.md](SECURITY_CHECK_RESULT.md)
- [COMMERCIAL_READY_CHECKLIST.md](COMMERCIAL_READY_CHECKLIST.md)
