# PHASE 0 — AUDIT & BASELINE

Tanggal: 2026-08-19
Branch: `experimental/demo-lms`
Repository: `ilhamrizqiawan21/lms_alihsan`

## Tujuan

Phase 0 menetapkan baseline teknis sebelum implementasi fitur baru. Audit ini membedakan antara fitur yang sudah ada, area yang masih menjadi technical debt, dan pekerjaan yang wajib masuk phase berikutnya.

## 1. Baseline Stack

- PHP `^8.3`
- Laravel `^13.8`
- Inertia Laravel `^3.1`
- Vue `^3.5`
- Bootstrap `^5.3`
- Vite `^6.4`
- MySQL 8 / MariaDB 10.6+ sebagai target database
- DomPDF untuk PDF
- OpenSpout untuk Excel

## 2. Arsitektur

Struktur aplikasi sudah memisahkan controller berdasarkan role:

- `app/Http/Controllers/Admin`
- `app/Http/Controllers/Guru`
- `app/Http/Controllers/Siswa`
- `app/Http/Controllers/Kepsek`
- service umum di `app/Services`
- model di `app/Models`
- policy di `app/Policies`

Arsitektur yang didokumentasikan menetapkan controller sebagai orkestrator request/response dan service sebagai tempat business logic yang panjang atau lintas model.

## 3. Fitur yang Teridentifikasi Sudah Diimplementasikan

- Authentication dan role-based access.
- Dashboard Admin, Guru, Siswa, Kepala Sekolah.
- User management.
- Kelas, siswa, mata pelajaran, penugasan guru.
- Tahun ajaran dan semester aktif.
- Wali kelas.
- Absensi.
- Materi pembelajaran.
- Tugas dan pengumpulan tugas.
- Nilai akademik.
- Sikap spiritual dan sosial.
- Chat.
- Notifikasi.
- Pengumuman.
- Kalender.
- Rekap akademik.
- Export PDF dan Excel.
- Import siswa Excel.
- Pengaturan branding sekolah.
- Audit login/error/akademik.
- Blocked IP.
- Demo seeder dan empty-product seeder.

## 4. Testing Baseline

Repository memiliki PHPUnit configuration dengan SQLite in-memory untuk test environment dan suite Unit/Feature.

Feature tests yang terdeteksi:

- `ExampleTest`
- `ErrorPageTest`
- `SecurityHeadersTest`
- `UploadValidationTest`
- `WaliKelasFeatureTest`

Dokumentasi manual test branch mencatat bahwa pada audit sebelumnya:

- migration fresh + seed berhasil;
- Vite build berhasil;
- login empat role berhasil;
- branding berhasil;
- export Excel berhasil;
- export PDF berhasil;
- `php artisan test` dilaporkan berhasil dengan 3 test pada saat dokumentasi dibuat.

**Catatan:** hasil tersebut adalah evidence historis dari dokumen repository, bukan hasil eksekusi baru pada 2026-08-19. Karena environment runtime tidak tersedia di connector, Phase 0 tidak mengklaim build/test baru telah dijalankan.

## 5. Temuan Technical Debt

### P1 — ExportController terlalu besar

`app/Http/Controllers/ExportController.php` berukuran sekitar 55 KB dan menangani banyak jenis laporan sekaligus. Ini berisiko meningkatkan coupling dan membuat perubahan laporan lebih sulit diuji.

Rencana phase berikutnya: pecah business logic menjadi report services tanpa mengubah kontrak route terlebih dahulu.

### P1 — routes/web.php terlalu besar

Semua role dan fitur masih berada pada satu route file. Dokumentasi arsitektur sendiri mengidentifikasi ini sebagai area yang dapat dipecah ketika pertumbuhan fitur berlanjut.

Rencana: pecah secara bertahap menjadi route group per role dengan mempertahankan nama route dan middleware.

### P1 — Coverage test belum proporsional terhadap luas fitur

Jumlah feature test yang terdeteksi masih kecil dibanding jumlah domain LMS: authentication, authorization, user, kelas, mapel, absensi, materi, tugas, submission, nilai, sikap, chat, notification, calendar, export, import, dan dashboard.

Rencana: tambah test berdasarkan domain dan authorization boundary, bukan sekadar mengejar jumlah coverage.

### P2 — Runtime verification belum menjadi bagian CI yang terbukti

Dokumentasi menyebutkan test/build manual berhasil, tetapi dari repository yang diaudit tidak ada bukti baru bahwa seluruh pipeline tersebut dijalankan pada commit saat ini.

Rencana: phase testing/production akan menetapkan command verification yang wajib lulus.

### P2 — Export layer perlu refactoring terkontrol

Refactoring tidak boleh dilakukan sekadar untuk mengecilkan file. Prioritasnya menjaga output PDF/Excel, filter, authorization, dan nama route tetap kompatibel.

## 6. Security Baseline

Audit repository mendokumentasikan:

- `.env` tidak dikomit;
- guard Laravel digunakan untuk autentikasi;
- middleware role digunakan;
- CSRF Laravel aktif;
- password menggunakan hashing Laravel;
- upload memiliki validasi tipe;
- import siswa memakai transaction;
- export temporary file menggunakan system temporary directory;
- blocked IP diperiksa pada middleware.

Risiko production yang masih wajib dipastikan:

- `APP_DEBUG=false`;
- `APP_KEY` unik;
- database user memiliki privilege minimal;
- password default diganti;
- `.env` dan log terlindungi;
- storage permission benar;
- backup terjadwal;
- rate limiting login dipertimbangkan;
- authorization per aksi terus diaudit saat fitur bertambah.

## 7. Database Baseline

Migration tersusun dalam kelompok master, akademik, support, dan migration lanjutan untuk nilai serta school settings. Terdapat migration khusus untuk perbaikan zero division pada nilai akhir dan migration penambahan komponen nilai.

Database memiliki domain utama yang konsisten dengan model LMS single-school.

## 8. Repository Hygiene

- README tersedia dan menjelaskan instalasi, role, demo account, empty product, branding, dokumentasi, dan production notes.
- `.env.example` tersedia.
- `composer.lock` tersedia.
- `package.json` tersedia.
- Dokumentasi architecture, code audit, security, manual test, import, branding, dan commercial readiness tersedia.
- Tidak ditemukan issue GitHub terbuka pada repository saat audit.
- Pencarian repository untuk marker `TODO/FIXME/unfinished/not implemented/placeholder` tidak mengembalikan hasil yang dapat diverifikasi melalui GitHub search.

## 9. Keputusan Baseline

Phase 0 **tidak menemukan indikasi bahwa aplikasi harus direwrite**. Fondasi saat ini layak dikembangkan secara incremental.

Prioritas engineering setelah Phase 0:

1. Stabilkan dan verifikasi core authorization/authentication.
2. Pastikan seluruh domain utama memiliki test yang memadai.
3. Selesaikan gap fitur yang benar-benar belum lengkap berdasarkan acceptance criteria.
4. Refactor route/export hanya ketika perubahan dapat dijaga kompatibilitasnya.
5. Lakukan runtime/build/security verification sebelum setiap milestone besar.

## 10. Batas Phase 0

Phase 0 adalah audit dan baseline, bukan implementasi fitur baru. Tidak ada perubahan perilaku aplikasi yang diperlukan hanya untuk menyelesaikan audit ini.

**Status: COMPLETE — baseline repository telah ditetapkan dan technical-debt backlog telah dibuat.**

## Evidence Repository

- `README.md`
- `docs/ARCHITECTURE.md`
- `docs/CODE_AUDIT.md`
- `docs/MANUAL_TEST_RESULT.md`
- `docs/SECURITY_CHECK_RESULT.md`
- `docs/COMMERCIAL_READY_CHECKLIST.md`
- `composer.json`
- `package.json`
- `phpunit.xml`
- `routes/web.php`
- `app/Http/Controllers/ExportController.php`
- `database/migrations/*`
- `tests/Feature/*`
