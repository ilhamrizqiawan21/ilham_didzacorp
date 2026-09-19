# IMPORT_SISWA.md

Panduan ini menjelaskan fitur import siswa massal dari Excel untuk role admin.

## Lokasi Menu

```text
Admin > Data User
```

Di halaman tersebut tersedia:

- Tombol `Template Siswa`
- Form `Import Siswa dari Excel`
- Tombol `Upload Excel`

## Route

```text
GET  /admin/users/import-siswa/template
POST /admin/users/import-siswa
```

Nama route:

```text
admin.users.import-siswa.template
admin.users.import-siswa
```

## Format Template

Sheet pertama bernama `Template Siswa` dan harus memiliki header berikut:

| Kolom | Wajib | Keterangan |
|---|---:|---|
| `username` | Ya | Username login siswa, unik di tabel `users` |
| `nama_lengkap` | Ya | Nama lengkap siswa |
| `nis` | Ya | NIS siswa, unik di tabel `siswa` |
| `kelas_id` | Ya | ID kelas dari sheet `Daftar Kelas` |
| `password` | Ya | Minimal 6 karakter |
| `jenis_kelamin` | Tidak | `L` atau `P` |
| `angkatan` | Tidak | Contoh `2026` |
| `status` | Tidak | `aktif`, `lulus`, atau `keluar`; default `aktif` |
| `is_active` | Tidak | `1/0`, `true/false`, `ya/tidak`, atau `aktif/nonaktif`; default aktif |

Sheet kedua bernama `Daftar Kelas` dan membantu admin memilih `kelas_id`.

## Aturan Import

- File harus `.xlsx`.
- Maksimal file mengikuti validasi controller saat ini: 5 MB.
- Maksimal 500 siswa per file agar proses import tetap stabil.
- Header sheet pertama harus sama dengan template terbaru.
- Baris kosong dilewati.
- Duplikasi `username` dan `nis` dicek di dalam file dan database.
- Jika ada satu baris error, seluruh import dibatalkan.
- Jika valid, sistem membuat data di `users` dan `siswa` dalam satu transaksi.

## Lokasi Kode

```text
app/Http/Controllers/Admin/UserController.php
app/Services/SiswaTemplateService.php
app/Services/SiswaImportService.php
resources/views/admin/users/index.blade.php
routes/web.php
```

## Catatan Maintenance

Jika kolom template berubah, update di satu tempat:

```php
App\Services\SiswaTemplateService::HEADERS
```

Lalu sesuaikan validasi dan proses insert di:

```text
app/Services/SiswaImportService.php
```
