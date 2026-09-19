# TODO: Hardening LMS Al Ihsan Academy

Repository: `alihsan-academy/lms.alihsan-academy`

## Ringkasan Audit

Audit ini mengganti asumsi TODO lama yang keliru.

- Stack aktual adalah Laravel 13, PHP 8.3, Inertia.js, Vue 3, Bootstrap, Vite, MySQL/MariaDB.
- Tidak ditemukan `src/app/api`, Next.js route handler, Supabase client, Supabase RLS, atau `SUPABASE_SERVICE_ROLE_KEY`.
- Tidak ada role `superadmin`. Role valid saat ini: `admin`, `guru`, `siswa`, `kepala_sekolah`.
- Tidak ada `routes/api.php`; seluruh endpoint utama memakai route web/session di `routes/web.php`.
- Area dashboard sudah dibatasi server-side dengan `auth` + `role:*` middleware:
  - `/admin/**` -> `admin`
  - `/guru/**` -> `guru`
  - `/siswa/**` -> `siswa`
  - `/kepsek/**` -> `kepala_sekolah`
- Object-level authorization sudah ada untuk banyak route guru/wali kelas melalui Gate/policy:
  - `can:mengajar,kelasMapel`
  - `can:mengajar-tugas,tugas`
  - `can:kelola-wali-kelas,waliKelas`
  - `can:lihat-laporan-wali-kelas,waliKelas`
- `SensitiveEndpointGuard` sudah mengintersep export status password dan reset password agar tidak mengekspor password default mentah.
- Test suite Laravel sudah ada. Tidak ditemukan konfigurasi Playwright/E2E. `package.json` hanya punya `dev` dan `build`.
- CI GitHub Actions sudah menjalankan `composer validate --strict`, `composer lint`, `php artisan test --colors=never`, dan `npm run build` dengan timeout/concurrency.

## Prinsip Pelaksanaan

- Jangan mengubah atau menghapus data production.
- Jangan commit `.env`, secret, token, dump database, atau credential demo production.
- Kerjakan di branch baru, misalnya `fix/security-and-production-readiness`, jika akses Git memungkinkan.
- Gunakan perubahan kecil dan commit terfokus.
- Untuk perubahan schema, buat migration Laravel yang bisa direview. Jangan jalankan migration ke production tanpa persetujuan.
- Pertahankan perilaku bisnis dan tampilan, kecuali perubahan diperlukan untuk keamanan.
- Jalankan minimal `composer test`, `php artisan test`, `npm run build`, dan Pint untuk file yang disentuh sebelum menyatakan selesai.
- Catat asumsi, risiko, dan bagian yang belum bisa diverifikasi.

## P0 - Keamanan Kritis

### 1. Hardening Admin User Management

Audit dan perbaiki:

- `routes/web.php` route group `admin`
- `app/Http/Controllers/Admin/UserController.php`
- `app/Http/Controllers/Admin/KelasSiswaController.php`
- `app/Http/Middleware/SensitiveEndpointGuard.php`
- `database/seeders/RoleSeeder.php`

Temuan audit:

- Route admin user sudah berada di `auth` + `role:admin`.
- Controller `UserController` masih sangat bergantung pada route middleware; mutasi belum punya guard eksplisit di controller.
- Role input memakai `exists:roles,id`; sebaiknya whitelist role yang boleh dikelola dari menu tersebut.
- `staffRoles()` mengecualikan `siswa`, tetapi belum memakai daftar role eksplisit.
- `exportExcel()` di controller masih membangun XLSX berisi kolom password default; saat runtime route ini diintersep `SensitiveEndpointGuard`, tetapi controller legacy tetap berbahaya jika middleware berubah/terlepas.
- `resetPassword()` memakai password default `User::DEFAULT_PASSWORD` sesuai keputusan operasional; controller dan middleware tetap harus menyimpan hash saja dan membatasi akses admin.
- Role `superadmin` tidak ada. Putuskan secara eksplisit apakah `admin` adalah role tertinggi, atau tambahkan role baru melalui migration/seed dan update UI.

Tugas:

- Tambahkan guard eksplisit di controller untuk semua mutasi user dan siswa admin:
  - `store`, `update`, `destroy`
  - `toggleActive`, `resetPassword`
  - `importSiswa`, `downloadSiswaTemplate`
  - `storeSiswa`, `updateSiswa`, `destroySiswa`, `luluskanKelas`
- Gunakan identitas dari session Laravel (`$request->user()` / `Auth::user()`), bukan dari payload browser.
- Standarkan respons JSON untuk request `expectsJson()`:
  - `401` untuk belum login
  - `403` untuk role salah
  - redirect login tetap dipertahankan untuk request web biasa
- Buat daftar role valid terpusat, misalnya `app/Support/RoleAccess.php` atau enum PHP:
  - semua role valid: `admin`, `guru`, `siswa`, `kepala_sekolah`
  - role staf yang bisa dikelola dari `Admin/Users`: `admin`, `guru`, `kepala_sekolah`
  - role siswa hanya dikelola dari `Admin/KelasSiswa`
- Ganti validasi `role_id` dari `exists:roles,id` umum menjadi whitelist role sesuai konteks.
- Pastikan export status password tidak pernah menampilkan password default atau password sementara.
- Keputusan operasional saat ini: reset password tetap ke `User::DEFAULT_PASSWORD` (`123456`). Mitigasi yang tetap wajib: simpan hash saja, jangan bocorkan lewat export/log/JSON, dan minta user mengganti password setelah login.
- Bungkus operasi create/update/delete user+siswa dalam transaksi Laravel.
- Tambahkan kompensasi/rollback aman untuk kegagalan import siswa.

Test wajib:

- Anonymous tidak bisa `POST/PATCH/DELETE` admin user dan admin siswa.
- Role `guru`, `siswa`, `kepala_sekolah` mendapat `403` untuk mutasi admin.
- `admin` masih bisa membuat/mengubah/menghapus akun staf sesuai aturan.
- Role `siswa` tidak bisa dipilih di form `Admin/Users`.
- Export status password tidak mengandung `User::DEFAULT_PASSWORD`.
- Reset password tetap memakai password default statis sesuai keputusan operasional, tetapi tidak boleh bocor di export/log/JSON dan harus ditandai `is_password_default`.
- Test controller guard tetap gagal aman jika route middleware tidak sengaja dilepas pada test route khusus.

Kriteria selesai:

- Admin user management tidak bisa diakses oleh anonymous/non-admin.
- Tidak ada password mentah/default di export, log, atau response JSON. Flash/session reset boleh menampilkan `123456` hanya kepada admin yang menjalankan reset.
- Role assignment hanya menerima role yang sah untuk konteksnya.

### 2. Hardening Login, Session, dan Role Redirect

Audit dan perbaiki:

- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Middleware/CheckRole.php`
- `app/Http/Middleware/RequirePasswordChange.php`
- `bootstrap/app.php`
- `resources/js/Pages/Auth/Login.vue`

Temuan audit:

- Login memakai session Laravel dan credential `username`/`email` + password.
- Redirect role sudah server-side lewat `redirectToByRole()` dan `intendedUrlIsAllowedForRole()`.
- Tidak ditemukan endpoint profile browser yang menerima `userId`.
- `CheckRole` mengembalikan redirect untuk user tanpa session; untuk request JSON/API perlu respons `401`.
- `bootstrap/app.php` saat ini hanya memaksa JSON rendering untuk `api/*`, bukan semua request `expectsJson()`.

Tugas:

- Pastikan semua identitas user selalu dari session server.
- Perbarui exception rendering agar request `expectsJson()` mendapat JSON aman untuk `401`, `403`, `404`, `422`, dan `500`.
- Pastikan pesan error login tidak membocorkan apakah username/email valid.
- Pastikan intended URL lintas host/scheme/role ditolak.
- Pastikan inactive user langsung logout, session invalidated, dan token diregenerasi.
- Tambahkan test redirect role untuk `admin`, `guru`, `siswa`, `kepala_sekolah`.
- Tambahkan test request JSON unauthenticated ke route web protected harus `401`, bukan `302`.

Kriteria selesai:

- User tidak bisa membuka dashboard role lain lewat intended URL.
- Request JSON mendapat status yang benar dan pesan aman.
- Login seluruh role tetap berjalan.

### 3. Hardening Ownership Guru dan Siswa

Audit dan perbaiki:

- `app/Http/Controllers/Guru/AbsensiController.php`
- `app/Http/Controllers/Guru/KelasDaringController.php`
- `app/Http/Controllers/Guru/TugasController.php`
- `app/Http/Controllers/Guru/MateriController.php`
- `app/Http/Controllers/Guru/NilaiController.php`
- `app/Http/Controllers/Guru/SikapController.php`
- `app/Http/Controllers/Guru/JadwalMengajarController.php`
- `app/Http/Controllers/Guru/WaliKelasController.php`
- `app/Http/Controllers/Guru/ChatController.php`
- `app/Http/Controllers/Siswa/*`
- `app/Policies/KelasMapelPolicy.php`
- `app/Policies/TugasPolicy.php`
- `app/Policies/WaliKelasPolicy.php`
- `app/Providers/AppServiceProvider.php`
- `routes/web.php`

Temuan audit:

- Banyak route guru sudah memakai `can:mengajar,kelasMapel`.
- `TugasController` mengecek task, submission, file, dan siswa terhadap parent resource.
- Siswa hanya membaca data berdasarkan relasi `Auth::user()->siswa` dan `kelas_id`.
- Absensi guru memvalidasi `siswa_id` harus berasal dari kelas pada `kelas_mapel` aktif.
- Kelas daring guru memfilter `kelas_mapel_id` berdasarkan `guru_id = Auth::id()` dan `aktif()`.
- Ada beberapa operasi batch yang belum dibungkus transaksi penuh, misalnya simpan absensi/nilai/sikap + audit/notifikasi.
- Route guru tambahan sebelumnya sempat terpisah di `routes/phase10-security.php`, tetapi sekarang sudah dikonsolidasikan ke `routes/web.php`.

Tugas:

- Tambahkan negative tests IDOR lintas guru untuk:
  - absensi create/store/export/rekap
  - tugas list/create/delete/pengumpulan/nilai/download
  - materi list/store/delete/download
  - nilai input/store/export
  - sikap input/store/export
  - kelas daring update status/delete
  - chat show/send
  - wali kelas absensi/pertemuan/penanganan
- Tambahkan negative tests lintas siswa untuk:
  - kelas-mapel siswa lain
  - tugas siswa lain
  - submission/file siswa lain
  - chat kelas lain
  - materi kelas lain
  - notifikasi user lain
- Pastikan setiap nested route memvalidasi parent-child relationship di controller, bukan hanya di UI.
- Bungkus batch write yang multi-record dalam transaksi.
- Pastikan notifikasi/audit log tidak membuat operasi utama setengah sukses; tentukan mana yang wajib dan mana best-effort.
- Selesai: konsolidasikan route guru dari `routes/phase10-security.php` ke struktur route utama.

Kriteria selesai:

- Percobaan IDOR lintas guru/siswa ditolak dengan `403` atau `404` sesuai konteks.
- Tidak ada mutasi multi-record yang bisa meninggalkan data setengah jadi.
- Policy/Gate tercatat jelas dan diuji.

### 4. Proteksi Dashboard dan Area Role

Audit dan perbaiki:

- `routes/web.php`
- `app/Http/Middleware/CheckRole.php`
- `app/Http/Middleware/CheckActive.php`
- `app/Http/Middleware/RequirePasswordChange.php`

Temuan audit:

- Proteksi role sudah ada untuk `/admin`, `/guru`, `/siswa`, dan `/kepsek`.
- TODO lama menyebut `/student`, `/teacher`, `/superadmin`; route aktual adalah `/siswa`, `/guru`, `/admin`, `/kepsek`.

Tugas:

- Tambahkan test akses lintas role untuk semua dashboard.
- Tambahkan test inactive user pada route protected.
- Pastikan request biasa redirect login, sedangkan request JSON mendapat `401`.
- Pastikan tidak ada redirect loop saat `RequirePasswordChange` aktif.
- Pastikan route root/login untuk user yang sudah login tidak membuat user bisa masuk role salah.

Kriteria selesai:

- Dashboard role salah selalu ditolak.
- User inactive tidak bisa mempertahankan session aktif.
- Password-change flow tidak loop.

## P1 - Database dan Integritas Data

### 5. Audit Schema, Constraint, dan Foreign Key

Audit dan perbaiki:

- `database/migrations/*`
- model:
  - `User`, `Role`, `Siswa`, `Kelas`, `KelasMapel`, `Tugas`, `PengumpulanTugas`, `PengumpulanFile`
  - `Absensi`, `NilaiAkhir`, `SikapSosial`, `SikapSpiritual`, `WaliKelas`, `KelasDaring`

Temuan audit:

- Tidak ada Supabase/RLS. Gunakan constraint database Laravel/MySQL dan policy aplikasi.
- `roles.nama_role` dan `siswa.user_id` ditutup oleh migration `2026_08_31_000001_add_missing_integrity_constraints.php`.
- `kelas_mapel` sudah punya migration unique tambahan.
- `absensi`, `nilai_akhir`, `pengumpulan_tugas`, dan `wali_kelas` sudah punya unique scope.
- `sikap_sosial` dan `sikap_spiritual` ditutup oleh migration `2026_08_31_000001_add_missing_integrity_constraints.php`.

Status implementasi:

- Migration constraint P1 sudah ditambahkan dengan guard deteksi duplikasi sebelum membuat index unik.
- Test constraint SQLite tersedia di `tests/Feature/P1DatabaseIntegrityTest.php`.

Tugas:

- Tambahkan migration untuk constraint yang kurang setelah audit data existing:
  - `roles.nama_role` unique
  - `siswa.user_id` unique
  - `absensi` unique: `siswa_id`, `kelas_mapel_id`, `tanggal`
  - `nilai_akhir` unique: `siswa_id`, `kelas_mapel_id`, `tahun_ajaran_id`, `semester`
  - `sikap_sosial` dan `sikap_spiritual` unique dengan key akademik yang sama
  - `pengumpulan_tugas` unique: `tugas_id`, `siswa_id`
  - `wali_kelas` unique: `kelas_id`, `tahun_ajaran_id`
- Buat migration data cleanup terpisah bila duplikasi ditemukan.
- Pastikan migration aman untuk MySQL/MariaDB dan SQLite test.
- Jangan menjalankan migration production sebelum backup dan review data.

Kriteria selesai:

- Integritas satu-satu/satu-record-per-konteks dijaga database, bukan hanya controller.
- Migration bisa dijalankan di test environment.

### 6. Tingkatkan Integritas Operasi Pengguna

Audit dan perbaiki:

- `Admin\UserController`
- `Admin\KelasSiswaController`
- `SiswaImportService`
- `SiswaTemplateService`
- `SensitiveEndpointGuard`

Tugas:

- Validasi email, username, NIS/NIP, role, jenis kelamin, kelas, status, dan password secara eksplisit.
- Normalisasi username/NIS/NIP agar unique check konsisten.
- Keputusan operasional: akun baru/reset boleh memakai password default statis `123456`, tetapi wajib ditandai `is_password_default` dan tidak boleh muncul di export/log/JSON.
- Pastikan import siswa:
  - tidak membuat user tanpa siswa;
  - tidak membuat siswa tanpa user;
  - melaporkan baris gagal tanpa mencetak data sensitif;
  - rollback per-baris atau per-file sesuai keputusan bisnis.
- Pastikan delete user/siswa mematuhi FK dan aturan retensi riwayat akademik.

Status implementasi:

- Input staff dinormalisasi dan divalidasi dengan whitelist karakter untuk username/NIP.
- Input NIS siswa admin divalidasi dengan whitelist karakter dan tetap dinormalisasi uppercase.
- Import siswa menormalisasi NIS uppercase dan diuji tidak membuat orphan user/siswa.
- Kebijakan password default `123456` tetap dipertahankan sesuai keputusan operasional dan ditandai `is_password_default`.

Kriteria selesai:

- Tidak ada orphan user/siswa.
- Tidak ada duplikasi profil siswa.
- Kegagalan parsial tidak meninggalkan data rusak.

### 7. Perbaiki Penanganan Waktu

Audit dan perbaiki:

- `JadwalMengajar`
- `KelasDaring`
- `CalendarEvent`
- `AbsensiController::attendanceMeetings`
- seluruh penggunaan `date()`, `strtotime()`, `now()`, dan parsing Carbon.

Temuan audit:

- App memakai `APP_TIMEZONE=Asia/Jakarta` di `.env.example`.
- `config/app.php` membaca `APP_TIMEZONE` dengan fallback `Asia/Jakarta`.
- TODO lama menyebut `Europe/London`, tetapi tidak ditemukan sebagai kebutuhan aktual.
- Masih ada penggunaan `date()`/`strtotime()` di beberapa controller.

Status implementasi:

- Timezone aplikasi sekarang membaca `APP_TIMEZONE`; fallback tetap `Asia/Jakarta`.
- Refactor kalkulasi tanggal manual yang lebih besar masih masuk pekerjaan lanjutan agar tidak mencampur perubahan perilaku.

Tugas:

- Standarkan timezone aplikasi dan dokumentasikan apakah semua input jadwal memakai timezone sekolah.
- Hindari kalkulasi tanggal manual jika Carbon bisa dipakai.
- Simpan datetime dalam timezone database yang disepakati; tampilkan memakai timezone aplikasi.
- Tambahkan test untuk batas hari, bulan, dan jadwal hari libur.

Kriteria selesai:

- Jadwal, absensi, deadline tugas, dan kelas daring konsisten di timezone sekolah.

## P1 - Validasi, Error, dan Logging

### 8. Standarkan Validasi Request Laravel

Audit dan perbaiki:

- Controller admin/guru/siswa yang menerima `Request`.
- Pertimbangkan Form Request class untuk area kompleks.

Tugas:

- Buat Form Request untuk request kompleks:
  - admin user/staff
  - admin siswa
  - import siswa
  - kelas-mapel/wali kelas/jadwal
  - absensi
  - nilai/sikap
  - tugas/materi upload
  - kelas daring
  - pengumuman
- Gunakan `Rule::in`, `Rule::exists()->where(...)`, dan custom rule sesuai konteks ownership.
- Validasi URL meeting, ukuran file, ekstensi file, tanggal, enum status, dan panjang string.
- Pastikan invalid nested IDs ditolak sebelum write.

Kriteria selesai:

- Controller lebih tipis dan validasi konsisten.
- Request tidak bisa membawa field/ID yang tidak sah untuk konteks user.

### 9. Standarkan Error Response dan Logging

Audit dan perbaiki:

- `bootstrap/app.php`
- `SystemError`
- `SensitiveDataRedactor`
- controller yang memanggil `report($e)`, `abort()`, atau mengembalikan pesan exception.

Temuan audit:

- Error 5xx dilog ke `SystemError` dengan redactor.
- Request JSON `expectsJson()` sudah dipaksa JSON oleh `shouldRenderJsonWhen`.
- Ada pesan error user-facing yang masih perlu distandarkan.

Status implementasi:

- Test JSON error aman tersedia di `tests/Feature/ErrorPageTest.php`.

Tugas:

- Pastikan JSON response untuk `expectsJson()` konsisten.
- Jangan mengirim pesan exception internal mentah ke client.
- Tambahkan request/correlation ID bila diperlukan.
- Pastikan redactor menutup password, token, cookie, authorization header, dan file path sensitif.
- Tambahkan test untuk error JSON aman.

Kriteria selesai:

- Error web tetap user-friendly.
- Error JSON punya format konsisten dan tidak membocorkan internal.

## P1 - Testing dan CI

### 10. Perluas Automated Test Coverage

Audit saat ini:

- PHPUnit/Laravel tests sudah tersedia di `tests/Feature` dan `tests/Unit`.
- Ada coverage penting seperti auth core, phase10 authorization, wali kelas, upload validation, security headers, dan frontend smoke.
- Belum ada E2E browser framework.

Tugas:

- Tambahkan test P0 yang disebut di atas.
- Tambahkan test untuk setiap route mutasi admin/guru/siswa yang rawan IDOR.
- Tambahkan test untuk JSON `401/403`.
- Tambahkan test integrity import siswa dan reset password.
- Tambahkan test migration constraint bila constraint baru dibuat.
- Tentukan apakah perlu Playwright E2E. Jika iya:
  - install Playwright;
  - buat seed/test database terpisah;
  - test login dan dashboard semua role;
  - jangan menyentuh production.

Kriteria selesai:

- Test negatif lintas role/lintas ownership tersedia.
- CI menjalankan semua test otomatis yang relevan.

### 11. Lengkapi Script Project

Audit saat ini:

- `composer.json` punya script `test`.
- `composer.json` punya script `lint` dan `format` scoped ke area hardening.
- `package.json` hanya punya `dev` dan `build`.
- Laravel Pint tersedia sebagai dependency dev.

Catatan:

- Full-repo Pint masih memiliki style debt lama di banyak file. Untuk menghindari diff formatting besar yang tidak terkait keamanan, `composer lint` saat ini sengaja dibatasi ke file hardening yang disentuh.

Tugas:

- Tambahkan script Composer:
  - `lint`: `pint --test`
  - `format`: `pint`
  - `test`: sudah ada, pastikan stabil
- Tambahkan script npm hanya jika tool frontend dipasang:
  - `lint`
  - `typecheck` tidak relevan kecuali TypeScript dipakai
  - `test:e2e` hanya jika Playwright benar-benar ditambahkan
- Jangan menambahkan script kosong yang selalu sukses.

Kriteria selesai:

- Developer dan CI punya command yang jelas untuk format, lint, test, dan build.

### 12. Perkuat GitHub Actions

Audit saat ini:

- `.github/workflows/ci.yml` sudah menjalankan install PHP/Node, test Laravel, dan build frontend.
- Workflow sudah punya `timeout-minutes`, `concurrency`, `composer validate --strict`, dan `composer lint`.

Tugas:

- Tambahkan `timeout-minutes`.
- Tambahkan `concurrency` untuk branch/PR.
- Jalankan `composer validate --strict`.
- Jalankan `composer lint` atau `vendor/bin/pint --test`.
- Jalankan `php artisan test --colors=never`.
- Jalankan `npm ci` dan `npm run build`.
- Jika ada E2E, jalankan pada job terpisah dengan database non-production.
- Pastikan workflow tidak mencetak secret.
- Dokumentasikan secret deployment yang diperlukan bila ada.

Kriteria selesai:

- PR gagal jika lint/test/build gagal.
- Deployment, bila ada, hanya jalan setelah job verifikasi hijau.

## P2 - Maintainability dan UI

### 13. Konsolidasi Route dan Naming

Temuan audit:

- Route guru tambahan dari `routes/phase10-security.php` sudah dikonsolidasikan ke `routes/web.php`.
- Loader tambahan di `bootstrap/app.php` sudah dihapus dan `routes/phase10-security.php` sudah tidak dipakai.
- Route download materi guru terdaftar sebagai `guru.materi.download`, bukan `guru.guru.materi.download`.
- Test route generation sudah ditambahkan di `tests/Feature/P2RouteConsolidationTest.php`.

Tugas:

- Selesai: konsolidasikan route phase10 ke `routes/web.php`.
- Selesai: perbaiki nama route ganda yang tidak dipakai publik.
- Selesai: jalankan `php artisan route:list --name=guru` untuk memverifikasi route guru.
- Selesai: tambahkan test route generation untuk link penting.

### 14. Refactor Controller Besar ke Service/Form Request

Temuan audit:

- Controller besar masih ada, terutama admin user/siswa dan modul guru.
- Sebagian risiko P0/P1 sudah diperkecil dengan guard eksplisit, whitelist role, normalisasi input, transaksi, dan test negatif.
- Filter/export/import admin user/siswa sudah memakai `StaffUserFilterRequest`, `SiswaFilterRequest`, dan `ImportSiswaRequest`.
- Validasi create/update staff admin sudah dipindahkan ke `StoreStaffUserRequest` dan `UpdateStaffUserRequest`.
- Validasi create/update siswa admin sudah dipindahkan ke `StoreSiswaRequest` dan `UpdateSiswaRequest`.
- Validasi penugasan admin kelas-mapel/wali kelas sudah dipindahkan ke `StoreKelasMapelRequest` dan `StoreWaliKelasRequest`.
- Validasi controller guru prioritas sudah dipindahkan ke Form Request:
  - `IndexAbsensiRequest`, `StoreAbsensiRequest`, `RekapAbsensiRequest`
  - `StoreKelasDaringRequest`, `UpdateKelasDaringStatusRequest`
  - `StoreMateriRequest`, `StoreBulkMateriRequest`
  - `StoreTugasRequest`, `StoreBulkTugasRequest`, `GradeTugasRequest`
  - `StoreNilaiRequest`, `StoreBulkNilaiRequest`, `RekapNilaiRequest`
  - `StoreSikapRequest`, `StoreBulkSikapRequest`, `RekapSikapRequest`
- Refactor penuh ke service/Form Request sebaiknya dilakukan bertahap per controller karena blast radius tinggi.
- Controller prioritas P2 berikut sudah tidak memiliki `$request->validate()` inline:
  - `Admin\UserController`
  - `Admin\KelasSiswaController`
  - `Admin\KelasMapelController`
  - `Guru\AbsensiController`
  - `Guru\TugasController`
  - `Guru\NilaiController`
  - `Guru\SikapController`
  - `Guru\MateriController`
  - `Guru\KelasDaringController`

Tugas:

- Pindahkan logic besar dari controller ke service/Form Request secara bertahap.
- Prioritas:
  - `Admin\UserController`
  - `Admin\KelasSiswaController`
  - `Admin\KelasMapelController`
  - `Guru\AbsensiController`
  - `Guru\TugasController`
  - `Guru\NilaiController`
  - `Guru\SikapController`
- Hindari refactor visual/behavioral besar tanpa test.
- Selesai: Form Request admin user/siswa, admin kelas-mapel/wali kelas, dan modul guru prioritas sudah dibuat.
- Selesai: ownership check untuk Form Request guru yang memakai model binding tetap dijaga di `authorize()` agar non-owner mendapat `403` sebelum validasi.
- Status lanjutan: ekstraksi service untuk controller besar masih bisa dikerjakan bertahap jika ingin memangkas ukuran controller lebih jauh tanpa mengubah perilaku.

### 15. Audit Frontend UX dan Aksesibilitas

Audit dan perbaiki:

- `resources/js/Pages/Auth/Login.vue`
- layout dashboard role
- form admin/guru/siswa
- upload tugas/materi

Tugas:

- Pastikan semua form punya label, error state, loading/disabled state, dan submit keyboard.
- Selesai sebagian: form prioritas admin/guru/siswa sudah punya label/error/loading state dari komponen form yang dipakai.
- Selesai sebagian: submit handler pada form prioritas admin/guru/siswa sudah punya guard `processing` selain tombol disabled untuk mengurangi double submit.
- Selesai sebagian: semua link `target="_blank"` yang terdeteksi di `resources/js` sudah memakai `rel="noopener noreferrer"`.
- Pastikan meeting URL tetap divalidasi server-side sebagai URL valid. Saat ini `Guru\KelasDaringController` memakai rule `required|url|max:500`.
- Periksa responsive mobile pada dashboard utama.
- Periksa contrast warna mengikuti checklist di `docs/FRONTEND_CONTRAST_CHECKLIST.md`.
- Status lanjutan: audit visual responsive/contrast penuh masih perlu browser/manual QA lintas viewport karena tidak ada konfigurasi E2E/Playwright di project saat ini.

## P2 - Dokumentasi

### 16. Perbarui README dan Installation Docs

Audit saat ini:

- README sudah menjelaskan stack, role, struktur, quick start, testing, security, dan dokumentasi.
- `.env.example` sudah ada.
- Perlu pastikan seluruh dokumen tidak lagi menyebut Supabase/Vercel bila tidak dipakai.

Tugas:

- Update README jika ada perubahan role/security/script.
- Update `docs/INSTALLATION.md` untuk production checklist aktual.
- Dokumentasikan script `composer lint`, `composer test`, `npm run build`, dan E2E bila ditambahkan.
- Dokumentasikan deployment target aktual, bukan Vercel kecuali memang digunakan.

### 17. Dokumentasikan Model Keamanan Aktual

Audit dan perbaiki:

- `docs/PHASE-10-SECURITY.md`
- `docs/security/phase10-authorization-matrix.md`

Tugas:

- Dokumentasikan authentication flow Laravel session.
- Dokumentasikan authorization matrix per role.
- Dokumentasikan ownership rule:
  - admin global pada data sekolah;
  - guru hanya kelas-mapel aktif yang diajar;
  - siswa hanya kelas aktifnya sendiri;
  - kepala sekolah read/monitoring sesuai scope;
  - notifikasi hanya milik `Auth::id()`.
- Dokumentasikan policy/Gate dan route middleware.
- Dokumentasikan prosedur rotasi `APP_KEY`, database password, mail/AWS credentials, dan akun admin awal.
- Dokumentasikan respons insiden jika credential atau `.env` bocor.

## Verifikasi Akhir

Sebelum menyatakan selesai:

- `composer install --no-interaction --prefer-dist --no-progress`
- `npm ci`
- `composer validate --strict`
- `vendor/bin/pint --test`
- `php artisan test`
- `npm run build`
- E2E non-production jika sudah ditambahkan
- `git diff --check`
- `php artisan route:list` untuk memastikan route protected sesuai role
- Review secret leakage:
  - `.env`
  - logs
  - docs
  - workflow output
  - response/flash yang mengandung password
- Review semua route admin/guru/siswa/kepsek yang memakai parameter model binding.
- Review semua export/download agar parent ownership diverifikasi.

## Hasil yang Harus Diserahkan

- Branch/PR berisi perubahan terfokus.
- Ringkasan masalah yang diperbaiki.
- Daftar file berubah.
- Migration SQL/Laravel migration bila ada.
- Test baru dan hasil test.
- Hasil Pint/lint, PHPUnit, build, dan E2E bila ada.
- Daftar environment variable/secret yang perlu dikonfigurasi.
- Risiko tersisa dan pekerjaan lanjutan.

## Definition of Done

Pekerjaan dianggap selesai hanya jika:

- Admin user/student management tidak bisa diakses anonymous/non-admin.
- Akses lintas role dan lintas ownership ditolak otomatis server-side.
- Password default/temporary tidak bocor di export, log, JSON response, atau flash yang tidak semestinya.
- Semua mutasi multi-record kritis transaksional atau punya kompensasi aman.
- Constraint database menjaga integritas data utama.
- Test negatif untuk role dan ownership lulus.
- Pint/lint, test, dan build lulus.
- CI memblokir PR/deploy yang gagal.
- Dokumentasi setup dan model keamanan sesuai stack Laravel aktual.
- Tidak ada secret atau perubahan data production.
