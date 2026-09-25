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

Aplikasi ini memakai 2 role: `guru` (juga berperan sebagai admin sekolah) dan `siswa`. Halaman dirender lewat Inertia/Vue (`resources/js/Pages`), bukan Blade per role; Blade hanya dipakai untuk dokumen PDF (lihat [Pola Export File](#pola-export-file)).

```text
app/Http/Controllers/Admin   # fitur administratif, tetap dijaga middleware role:guru
app/Http/Controllers/Guru
app/Http/Controllers/Siswa
resources/js/Pages/Admin
resources/js/Pages/Guru
resources/js/Pages/Siswa
```

Gunakan folder role jika fitur hanya dipakai role tersebut. Gunakan controller/service umum jika fiturnya lintas role, misalnya export laporan.

Catatan: aplikasi ini pernah memakai 4 role (admin, guru, siswa, kepala_sekolah/kepsek) dari basis kode LMS sebelumnya. Sisa controller dan halaman `Kepsek` yang sudah tidak ter-routing telah dihapus.

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
