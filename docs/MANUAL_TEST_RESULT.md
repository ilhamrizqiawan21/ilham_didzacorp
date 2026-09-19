# MANUAL_TEST_RESULT.md

Tanggal test: 2026-07-08

## Lingkungan Test

- Database: MySQL sesuai `.env`
- URL lokal: `http://127.0.0.1:8015`
- Seeder: `php artisan migrate:fresh --seed --force`
- Server: `php artisan serve --host=127.0.0.1 --port=8015`

## Ringkasan

Status: lulus untuk milestone aplikasi LMS single-school siap pakai.

Catatan penting: test MySQL pertama menemukan error nama index terlalu panjang pada migration `nilai_akhir`. Migration sudah diperbaiki dengan nama index eksplisit `nilai_akhir_unique_scope`, lalu `migrate:fresh --seed` berhasil.

## Hasil Test

| Item | Status | Bukti/Catatan |
|---|---|---|
| `php artisan migrate:fresh --seed` | Lulus | Semua migration `Ran`, seeder demo selesai. |
| `php artisan storage:link` | Lulus | Link sudah ada, tidak perlu dibuat ulang. |
| `npm run build` | Lulus | Vite build sukses dan menghasilkan asset di `public/build`. |
| `php artisan serve` | Lulus | Server berjalan di `http://127.0.0.1:8015`. |
| Login admin | Lulus | akun demo dengan password dari `DEMO_PASSWORD` redirect ke `/admin/dashboard`. |
| Login guru | Lulus | akun demo dengan password dari `DEMO_PASSWORD` redirect ke `/guru/dashboard`. |
| Login siswa | Lulus | akun demo dengan password dari `DEMO_PASSWORD` redirect ke `/siswa/dashboard`. |
| Login kepala sekolah | Lulus | akun demo dengan password dari `DEMO_PASSWORD` redirect ke `/kepsek/dashboard`. |
| Ubah nama sekolah | Lulus | Nama berubah menjadi `Sekolah Demo Manual` dan tampil di login. |
| Ubah logo | Lulus | Upload PNG berhasil, `logo_path` tersimpan di `school/...png`. |
| Ubah favicon | Lulus | Upload ICO berhasil, `favicon_path` tersimpan di `school/...ico`. |
| Refresh halaman | Lulus | Login page menampilkan nama, short name, alamat, dan logo baru. |
| Cetak laporan/browser print | Lulus sebagian | Halaman rekap admin absensi/nilai dapat diakses `200`; print memakai browser user. |
| Export Excel | Lulus | Export nilai Excel kelas ID 1 berhasil `200 application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`. |
| Export PDF | Lulus | Export absensi PDF kelas ID 1 berhasil `200 application/pdf`. |
| Clear cache | Lulus | `php artisan optimize:clear` sukses. |
| Test suite | Lulus | `php artisan test` sukses: 3 tests passed. |

## Akun Demo

| Role | Email | Password |
|---|---|---|
| Admin | `admin@demo.test` | `password` |
| Guru | `guru@demo.test` | `password` |
| Siswa | `siswa@demo.test` | `password` |
| Kepala Sekolah | `kepsek@demo.test` | `password` |

## Catatan Serah Terima

- Password default wajib diganti setelah instalasi.
- Data branding test dapat diganti ulang dari `Admin > Pengaturan Sekolah`.
- File `.env` lokal tidak boleh disertakan saat distribusi.
- Untuk instalasi klien kosong, gunakan `php artisan migrate:fresh --seeder=EmptyProductSeeder`.
