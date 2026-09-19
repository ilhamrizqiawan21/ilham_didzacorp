# Audit kesiapan deployment — 15 September 2026

## Keputusan

**NO-GO untuk langsung memindahkan instalasi demo ke domain utama.** Ada kegagalan quality gate, pengamanan akun awal dan kesiapan operasional yang perlu diselesaikan. Fondasi aplikasi cukup baik: seluruh tes backend lulus dan build berhasil.

Audit mencakup working tree saat ini, termasuk 10 file yang sebelumnya sudah dimodifikasi pengguna. Tidak dilakukan deployment, perubahan konfigurasi server, penggantian password, atau migrasi database demo. Query database demo hanya baca; tes menggunakan SQLite terpisah. Build audit diarahkan ke `/tmp/lms-audit-build`.

## Hasil pemeriksaan

| Pemeriksaan | Hasil |
|---|---|
| Composer validate | Lulus |
| PHPUnit | 90 tes, 866 assertion, 0 failure/error/skipped |
| Composer lint | Lulus; cakupannya hanya daftar file pada script Composer |
| Browser Playwright | 14 lulus, 1 gagal (2,1 menit) |
| TypeScript | Gagal TS2322 pada halaman Pengaturan Admin |
| Vite production build | Lulus; nama output pada manifest sesuai build yang terpasang |
| Composer audit / npm audit | Tidak ada advisory yang dilaporkan pada saat audit |
| Migrasi database demo | Seluruh 26 migrasi sudah diterapkan |
| HTTPS login dan `/up` | HTTP 200 |
| Akses HTTP `/.env`, `/.git/config`, `/storage/` | 404, 404, 403 |
| Queue database | 0 jobs dan 0 failed_jobs saat diperiksa |

## Temuan prioritas tinggi

### 1. CI gagal pada TypeScript — terkonfirmasi

- Lokasi: `resources/js/Pages/Admin/Pengaturan/Index.vue:338`.
- `TextareaInput` menerima `rows?: number`, tetapi pemanggil memberikan `rows="7"` yang bertipe string.
- Reproduksi: `npm run typecheck`; hasil `TS2322: Type 'string' is not assignable to type 'number'`.
- CI menjalankan typecheck sebelum tes/build, sehingga pipeline tidak dapat lulus.
- Perbaikan: gunakan `:rows="7"`, kemudian jalankan kembali typecheck.

### 2. Akun password default masih aktif — terkonfirmasi pada demo

- Dua akun aktif memiliki `is_password_default=true`; nilai konfigurasi `FORCE_PASSWORD_CHANGE=false`.
- `app/Models/User.php:17` mendefinisikan password bersama `123456`; proses pembuatan/reset user dan import siswa masih dapat menggunakannya.
- Hitungan berasal dari flag database, bukan percobaan login atau pemeriksaan hash.
- Jangan membawa akun/data demo ini ke produksi tanpa penanganan. Gunakan password awal acak per akun dan proses penggantian yang jelas; tentukan kebijakan wajib ganti password sebelum peluncuran.

### 3. Backup LMS dan pemulihan belum terbukti siap

- Crontab user menjalankan script backup harian, tetapi database MySQL pada konfigurasi backup tersebut berbeda dari database LMS.
- Tidak ditemukan bukti backup LMS maupun pengujian restore pada pemeriksaan ini. Backup dari mekanisme eksternal/root belum terverifikasi.
- Sebelum migrasi: siapkan backup database **dan file upload**, uji restore pada lingkungan terpisah, serta dokumentasikan rollback deployment.

### Quality gate tambahan: tes browser tidak sinkron dengan kebijakan telepon — terkonfirmasi

- Lokasi: `tests/Browser/migration.spec.ts:211`, `scripts/test-browser.mjs:16`, `config/security.php:6`.
- Tes mengharapkan siswa tanpa telepon diarahkan ke `/siswa/pengaturan`, tetapi hasil aktual `/siswa/dashboard`.
- `REQUIRE_STUDENT_PHONE` default `false`; runner tidak menetapkan flag tersebut, sehingga ekspektasi tes tidak sesuai konfigurasi. Ini bukan bukti dashboard rusak.
- Perbaikan: tetapkan flag eksplisit untuk skenario wajib telepon dan tambahkan skenario opsional sesuai kebijakan produk. Jalankan ulang keseluruhan browser suite.
- Empat role berhasil melewati pemeriksaan halaman serta responsive drawer; alur browser lain yang ada dalam suite juga lulus. Browser memakai asset terpasang yang manifest-nya cocok dengan hasil build audit. CSP dibypass pada runner HTTP lokal, sehingga hasil ini tidak memverifikasi CSP produksi.

## Bug dan risiko alur aplikasi

### 4. Pengingat WhatsApp meminta pengumpulan ulang untuk tugas yang sudah dikumpulkan — direproduksi

- Lokasi: `app/Services/WhatsAppService.php:24`.
- Filter hanya mengecualikan `dinilai` dan `perlu_perbaikan`; status `terlambat` yang sudah memiliki `tanggal_kumpul` masih dimasukkan.
- Reproduksi SQLite terpisah: deadline tiga hari lalu, submission kemarin dengan status `terlambat`. Pesan tetap menyertakan tugas dan meminta siswa mengumpulkan lagi.
- Padahal `Siswa/TugasController` menolak pengiriman ulang pada status tersebut.
- Perbaikan: pisahkan pengingat belum kumpul dari informasi penalti tugas yang sudah dikumpulkan; jangan meminta tindakan yang tidak dapat dilakukan siswa.

### 5. Penilaian tugas dan nilai harian tidak atomik — risiko berdasarkan inspeksi kode

- Lokasi: `app/Http/Controllers/Guru/TugasController.php:341` dan `:350`.
- Nilai submission disimpan, lalu `syncNilaiHarian()` dijalankan tanpa transaksi yang meliputi kedua write.
- Jika write kedua gagal, nilai tugas sudah berubah tetapi rekap nilai harian tertinggal. Kegagalan ini belum diinjeksi/direproduksi dalam audit.
- Perbaikan: bungkus kedua write dalam transaksi; tambah regression test yang mensimulasikan kegagalan sinkronisasi.

### 6. Perlombaan pengumpulan tugas — risiko berdasarkan inspeksi kode

- Lokasi: `app/Http/Controllers/Siswa/TugasController.php:180` dan `:194`.
- Status boleh kirim diperiksa sebelum transaksi, tanpa penguncian baris. Dua request bersamaan dapat sama-sama lolos dan memperbarui submission yang sama.
- Constraint unik menjaga satu baris submission, tetapi tidak menjamin hanya satu pengiriman diterima. File dapat bertambah dari dua request.
- Perbaikan: cek status kembali di dalam transaksi dengan penguncian/operasi kondisional dan uji dua request bersamaan. Belum dilakukan load/concurrency test.

## Proses operasional dan fitur yang belum selesai

- **Domain utama:** `APP_URL` masih `https://demo-lms.didzacorp.com`. Ini wajar untuk demo, tetapi harus diganti pada konfigurasi target. Domain utama belum disebutkan sehingga DNS, sertifikat, virtual host, dan sesi domain target belum diverifikasi. Symlink `public/storage` saat ini absolut ke direktori demo; buat ulang pada instalasi baru.
- **Scheduler:** task `system-errors-prune` tersedia di `routes/console.php`, tetapi tidak ditemukan cron/systemd LMS pada lokasi yang dapat diperiksa. Crontab user hanya memanggil scheduler proyek lain. Retensi error tidak berjalan melalui konfigurasi yang ditemukan.
- **Queue:** konfigurasi memakai `database`, worker tidak ditemukan pada snapshot proses. Namun notifikasi kelas memakai `afterResponse()`; jangan menyimpulkan notifikasi saat ini macet hanya karena worker tidak ada. Siapkan worker terkelola bila memakai job asinkron biasa.
- **WhatsApp:** implementasi saat ini membuka `wa.me` dan memakai penandaan terkirim manual. Tidak ada bukti pengiriman otomatis, webhook, atau konfirmasi delivery. Tombol pada detail siswa/halaman nilai dan filter khusus masih tercatat belum selesai pada roadmap; bukan blocker LMS dasar jika tidak dijanjikan sebagai fitur rilis.
- **Email:** `MAIL_MAILER=log`; belum siap untuk pengiriman email sungguhan jika dibutuhkan scope produksi.
- **Release:** 10 file sudah memiliki perubahan lokal sebelum audit. Bekukan versi rilis dan commit perubahan yang dipilih agar hasil tes sesuai versi yang dideploy.
- **Permission:** `storage` dan `bootstrap/cache` memiliki permission world-writable. Batasi penulisan ke user/group aplikasi sebelum produksi.
- **Dokumentasi:** sejumlah checkbox roadmap sudah tidak mencerminkan implementasi terbaru (misalnya Chart.js sudah dependency lokal). Jangan menganggap seluruh checkbox lama sebagai fitur rusak.

## Log error dan batas pemeriksaan

Database memiliki 118 catatan error; 38 berada pada tujuh hari terakhir ketika diperiksa: 31 ErrorException, 5 QueryException, dan 2 console RuntimeException. Error terakhir tercatat 12 September. Ini riwayat, bukan bukti bahwa semua error masih aktif; tes backend dan akses login saat audit berhasil. Perlu triase error lama berdasarkan alur pemicunya, terutama query database.

Tes backend/browser menggunakan SQLite; perilaku spesifik MySQL seperti generated columns belum diuji ulang pada database MySQL kosong. Pemeriksaan ini bukan pentest penuh, load test, atau verifikasi seluruh perangkat/mobile browser. Tidak ada pesan WhatsApp/email yang dikirim.

## Syarat sebelum GO

1. Typecheck dan seluruh tes browser lulus pada konfigurasi yang dituju.
2. Selesaikan kebijakan akun awal dan jangan salin akun demo tanpa penanganan.
3. Backup database/upload LMS serta restore dan rollback telah diuji.
4. Perbaiki pengingat WhatsApp; lindungi konsistensi penilaian/pengumpulan.
5. Siapkan domain target, storage link, scheduler, permission, serta worker bila diperlukan.
6. Uji migrasi pada MySQL terpisah dan smoke test login tiap role, import, upload/download, tugas, nilai, absensi, export, dan notifikasi di target.
