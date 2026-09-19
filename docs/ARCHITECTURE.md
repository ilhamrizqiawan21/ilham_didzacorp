# ARCHITECTURE.md

Dokumen ini menjelaskan penempatan kode utama LMS Sekolah agar pengembangan berikutnya konsisten.

## Prinsip Penempatan

- `routes/web.php` hanya berisi mapping URL ke controller dan middleware role.
- `app/Http/Controllers` berisi orkestrasi request, validasi request sederhana, pemilihan view, redirect, dan response download.
- `app/Services` berisi proses bisnis yang mulai panjang, dipakai ulang, atau menyentuh beberapa model sekaligus.
- `app/Models` berisi representasi tabel, relasi, casts, dan helper model yang dekat dengan data.
- `resources/views` berisi Blade sesuai role dan fitur.
- `docs` berisi panduan teknis, operasional, audit, dan checklist serah terima.

## Struktur Role

```text
app/Http/Controllers/Admin
app/Http/Controllers/Guru
app/Http/Controllers/Siswa
app/Http/Controllers/Kepsek
resources/views/admin
resources/views/guru
resources/views/siswa
resources/views/kepsek
```

Gunakan folder role jika fitur hanya dipakai role tersebut. Gunakan controller/service umum jika fiturnya lintas role, misalnya export laporan.

## Service Saat Ini

```text
app/Services/AbsensiService.php
app/Services/NilaiService.php
app/Services/NotifikasiService.php
app/Services/SiswaImportService.php
app/Services/SiswaTemplateService.php
app/Services/StatistikService.php
```

`SiswaImportService` mengatur parsing, validasi, dan transaksi import siswa dari Excel.

`SiswaTemplateService` mengatur pembuatan template Excel import siswa beserta sheet daftar kelas.

## Pola Export File

File export sementara dibuat di temporary directory sistem dengan `tempnam(sys_get_temp_dir(), ...)`, lalu dikirim dengan:

```php
return response()->download($filePath, $filename)->deleteFileAfterSend(true);
```

Pola ini menghindari error permission pada `storage/app/temp` di hosting/production.

## Pola Import Excel

- Template disediakan lewat route admin.
- Sheet pertama dipakai sebagai data import.
- Header harus sama dengan template.
- Validasi per baris mengumpulkan semua error.
- Import dilakukan all-or-nothing melalui transaksi database.

Detail operasional ada di [IMPORT_SISWA.md](IMPORT_SISWA.md).
