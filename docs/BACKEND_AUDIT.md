# Audit Backend

Tanggal audit: 2026-07-16

## Ringkasan

Backend aplikasi sudah memiliki fondasi yang cukup baik untuk aplikasi LMS berbasis role: route dipisah berdasarkan role, middleware role aktif digunakan, policy dipakai untuk akses guru/wali kelas, login sudah memakai rate limiter, dan beberapa tabel penting sudah memiliki unique constraint untuk menjaga duplikasi data.

Temuan utama yang perlu diprioritaskan adalah risiko password default statis `123456`, beberapa constraint integritas data yang belum dikunci di database, potensi bottleneck export besar karena query dan PDF masih diproses sinkron, serta kebutuhan memperkuat pola penyimpanan file agar materi/tugas tidak terlalu bergantung pada path publik.

## Ruang Lingkup

Area yang diperiksa:

- Routing backend di `routes/web.php`
- Middleware di `app/Http/Middleware`
- Auth dan login di `app/Http/Controllers/Auth`
- Controller role admin, guru, siswa, dan kepsek
- Policy akses guru dan wali kelas
- Model dan migration utama
- Upload, download, import, dan export
- Logging error dan login
- Struktur dokumentasi dan test yang tersedia

Tidak termasuk:

- Audit UI detail
- Penetration test langsung ke server produksi
- Audit konfigurasi server, permission OS, SSL, dan database production
- Review semua dependency keamanan terbaru dari internet

## Hal Yang Sudah Baik

- Route sudah dikelompokkan berdasarkan role `admin`, `guru`, `siswa`, dan `kepala_sekolah`.
- `CheckRole` tidak hanya memeriksa role, tetapi juga menolak user nonaktif dan melakukan logout.
- Login melakukan regenerasi session setelah autentikasi berhasil.
- Login sudah memakai rate limiter berdasarkan kombinasi username dan IP.
- Policy `KelasMapelPolicy` dan `WaliKelasPolicy` sudah membatasi akses guru pada kelas/mapel yang memang dia ajar.
- Banyak operasi penting sudah memakai validasi request eksplisit.
- Download tugas siswa memeriksa kepemilikan dan relasi siswa sebelum file dikirim.
- Beberapa tabel penting sudah memiliki unique constraint, misalnya absensi, pengumpulan tugas, nilai akhir, dan wali kelas.
- Import siswa sudah dipisahkan ke service sehingga lebih mudah diuji dan dikembangkan.
- Export Excel sudah memakai OpenSpout yang lebih cocok untuk file besar dibanding membangun XLSX manual penuh di memori.
- Error aplikasi dicatat ke tabel `system_errors` dengan pengecualian untuk error umum seperti validasi, 404, dan auth.

## Temuan Prioritas Tinggi

### 1. Password default statis `123456`

Evidence:

- `app/Models/User.php`
- `app/Http/Controllers/Admin/UserController.php`
- Service import siswa dan pembuatan akun baru

Saat ini akun baru dan reset password memakai default `123456`. Ini sesuai kebutuhan operasional, tetapi secara keamanan menjadi risiko tinggi karena password mudah ditebak dan sama untuk banyak akun.

Dampak:

- Akun baru yang belum diganti password mudah diambil alih.
- Reset password dapat diketahui oleh pihak lain karena nilainya sama dan tetap.
- Jika ada satu user lupa mengganti password, akses tetap terbuka.

Rekomendasi:

- Tambahkan flag `must_change_password` atau kolom `password_changed_at`.
- Setelah login dengan password default, paksa user mengganti password sebelum masuk dashboard.
- Catat event reset password ke audit log.
- Batasi informasi password di response UI. Hindari menampilkan password default terlalu eksplisit.
- Dalam jangka panjang, gunakan password acak sekali pakai atau link reset terbatas waktu.

### 2. Constraint unik nilai sikap belum dikunci di database

Evidence:

- Migration `sikap_spiritual`
- Migration `sikap_sosial`
- Controller menyimpan nilai sikap dengan pola `updateOrCreate`

Data sikap spiritual dan sosial memakai kombinasi `siswa_id`, `kelas_mapel_id`, `tahun_ajaran_id`, dan `semester` sebagai identitas logis. Namun kombinasi ini belum diberi unique constraint di database.

Dampak:

- Race condition, import manual, atau query langsung dapat membuat data duplikat.
- Laporan nilai dan rata-rata bisa mengambil data yang salah.
- `updateOrCreate` di aplikasi tidak cukup sebagai jaminan integritas data.

Rekomendasi:

- Tambahkan unique index untuk kedua tabel:
  - `siswa_id`
  - `kelas_mapel_id`
  - `tahun_ajaran_id`
  - `semester`
- Sebelum migration, buat script pembersihan duplikasi jika data production sudah berjalan.
- Tambahkan test untuk memastikan duplikasi ditolak.

### 3. Export besar masih berisiko timeout dan boros memori

Evidence:

- `app/Http/Controllers/ExportController.php`
- `app/Http/Controllers/Admin/KelasSiswaController.php`
- Route export PDF/Excel di `routes/web.php`

Export sudah lebih rapi dan Excel memakai OpenSpout. Namun beberapa query masih mengambil seluruh data dengan `get()` sebelum diproses. PDF juga dibangun sinkron dari seluruh data.

Dampak:

- Export kelas/siswa/nilai/absensi besar dapat timeout.
- Memory PHP dapat habis saat data sekolah bertambah.
- Request web menjadi lambat dan mengunci pengalaman admin/guru/kepsek.

Rekomendasi:

- Untuk Excel, gunakan `chunkById`, cursor, atau lazy collection sesuai struktur query.
- Berikan batas maksimal PDF, misalnya 500-1000 baris, lalu arahkan ke Excel untuk data besar.
- Untuk export besar, pindahkan ke queue dan kirim hasil melalui notifikasi/download history.
- Tambahkan indikator jumlah data sebelum export.

## Temuan Prioritas Sedang

### 4. `routes/web.php` terlalu besar dan memuat banyak tanggung jawab

Evidence:

- `routes/web.php`

Semua route admin, guru, siswa, kepsek, import, export, dan auth berada di satu file. Secara fungsi masih berjalan, tetapi makin sulit diaudit dan rawan konflik nama route.

Rekomendasi:

- Pecah route menjadi beberapa file:
  - `routes/admin.php`
  - `routes/guru.php`
  - `routes/siswa.php`
  - `routes/kepsek.php`
  - `routes/export.php`
- Load file tersebut dari `bootstrap/app.php` atau service provider route.

### 5. Penyimpanan file materi masih memakai disk publik

Evidence:

- `app/Http/Controllers/Guru/MateriController.php`
- `app/Http/Controllers/Siswa/TugasController.php`

Materi guru disimpan ke disk `public`. Download memang melalui controller dan authorization, tetapi jika storage public/symlink aktif, file bisa saja diakses langsung bila path diketahui. Download tugas siswa juga masih memiliki fallback ke disk public.

Dampak:

- Materi atau tugas dapat terbuka di luar mekanisme role jika path publik diketahui.
- Sulit memastikan semua akses file melewati authorization.

Rekomendasi:

- Simpan materi dan tugas pada disk private/local.
- Semua download file wajib melalui controller yang memeriksa role dan relasi data.
- Hilangkan fallback ke disk public jika tidak dibutuhkan lagi.
- Buat migration atau command pemindahan file lama dari public ke private.

### 6. Brute force protection belum terhubung penuh dengan blocked IP

Evidence:

- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Middleware/CheckBlockedIp.php`
- Model `BlockedIp`

Login sudah memakai rate limiter, dan aplikasi punya tabel blocked IP. Namun kegagalan login berulang belum otomatis membuat temporary block di tabel tersebut.

Rekomendasi:

- Tambahkan limiter per IP selain per username dan IP.
- Tambahkan log gagal login untuk audit.
- Jika threshold tinggi tercapai, masukkan IP ke `blocked_ips` dengan expiry.
- Tetap sediakan whitelist atau pengecualian untuk jaringan internal jika diperlukan.

### 7. Error log bisa menyimpan data sensitif

Evidence:

- `bootstrap/app.php`
- `app/Http/Controllers/Admin/SystemController.php`
- Model `SystemError`

Error handler menyimpan message, file, line, trace, URL, IP, user agent, dan user ID. Ini berguna untuk debugging, tetapi trace dan URL kadang mengandung data sensitif.

Status perbaikan:

- Sudah ditambahkan redaction untuk field sensitif seperti password, token, API key, authorization, cookie, dan secret.
- Sanitasi diterapkan saat exception handler menulis log dan juga di mutator model `SystemError`.
- URL log sekarang meredaksi query parameter sensitif.
- Log error lama dipangkas otomatis oleh scheduler dengan default retensi 90 hari melalui `SYSTEM_ERROR_RETENTION_DAYS`.
- Sudah ditambahkan unit test untuk memastikan data sensitif tidak tersimpan mentah.

Rekomendasi:

- Pastikan scheduler Laravel berjalan di server production.
- Sesuaikan `SYSTEM_ERROR_RETENTION_DAYS` bila sekolah membutuhkan retensi log lebih pendek atau lebih panjang.
- Review berkala daftar field sensitif jika nanti ada integrasi API baru.

### 8. Middleware `CheckActive` ada tetapi belum digunakan

Evidence:

- `app/Http/Middleware/CheckActive.php`
- `bootstrap/app.php`
- `routes/web.php`

Status aktif sudah dicek di `CheckRole`, tetapi `CheckActive` terpisah belum terdaftar/dipakai. Jika nanti ada route authenticated tanpa role middleware, user nonaktif bisa lolos bila hanya memakai `auth`.

Rekomendasi:

- Register alias `active` dan pakai pada semua route authenticated.
- Atau hapus middleware yang tidak digunakan agar tidak membingungkan.
- Tetapkan satu sumber kebenaran untuk validasi user aktif.

### 9. Controller besar menggabungkan query, transformasi, dan presentasi

Evidence:

- `app/Http/Controllers/ExportController.php`
- `app/Http/Controllers/Kepsek/LaporanController.php`
- `app/Http/Controllers/Guru/WaliKelasController.php`

Beberapa controller sudah cukup besar dan menangani banyak detail sekaligus. Ini membuat perubahan export/laporan lebih rawan regresi.

Rekomendasi:

- Pindahkan query laporan ke service atau query object.
- Pindahkan format export ke class khusus, misalnya `ExportBuilder` atau `ReportExportService`.
- Tambahkan test per service agar tidak harus selalu menguji lewat controller.

### 10. Unik `nip_nis` masih bergantung pada validasi aplikasi

Evidence:

- Migration `users`
- `app/Http/Controllers/Admin/UserController.php`
- Import siswa

`nip_nis` dipakai sebagai identitas staff/siswa, tetapi unique constraint belum terlihat di level database. Validasi aplikasi sudah membantu, tetapi tidak mencegah race condition atau perubahan langsung di database.

Rekomendasi:

- Jika `nip_nis` wajib unik lintas user, tambahkan unique index.
- Jika boleh null, pastikan database yang dipakai mendukung banyak null pada unique index sesuai ekspektasi.
- Bersihkan data duplikat sebelum migration.

### 11. Coverage test backend masih perlu diperluas

Evidence:

- Test suite sebelumnya berjalan, tetapi jumlah test masih sedikit dan beberapa skipped.

Area yang sebaiknya ditambahkan test:

- Login rate limiting dan user inactive.
- Akses role admin/guru/siswa/kepsek untuk route penting.
- Guru tidak bisa mengakses kelas/mapel guru lain.
- Siswa tidak bisa download tugas siswa lain.
- Reset password staff/guru dan siswa.
- Import siswa valid dan invalid.
- Export PDF/Excel untuk dataset kosong dan dataset besar.
- Constraint unik nilai sikap setelah migration.

## Temuan Prioritas Rendah

### 12. Semua 404 dirender sebagai maintenance view

Evidence:

- `bootstrap/app.php`

Not found dirender ke halaman maintenance. Ini mungkin sengaja untuk branding, tetapi dapat membingungkan karena resource tidak ditemukan berbeda dengan maintenance.

Status perbaikan:

- Sudah ditambahkan halaman khusus 404 di `resources/views/errors/404.blade.php`.
- Handler `NotFoundHttpException` sekarang merender halaman 404, bukan halaman maintenance.
- Request JSON/API tetap memakai response default framework.
- Sudah ditambahkan feature test agar halaman 404 tidak kembali memakai copy maintenance.

Rekomendasi:

- Pastikan copy halaman error tetap konsisten dengan branding sekolah.
- Gunakan maintenance view hanya untuk status maintenance/error server, bukan route yang tidak ditemukan.

### 13. Campuran Blade dan Inertia perlu batas arsitektur yang jelas

Evidence:

- Controller export memakai Blade PDF.
- Halaman utama memakai Inertia/Vue.

Campuran ini wajar, terutama untuk PDF. Namun batasnya perlu didokumentasikan agar pengembangan berikutnya konsisten.

Rekomendasi:

- Dokumentasikan bahwa Blade dipakai untuk PDF/email/server-rendered document.
- Inertia/Vue dipakai untuk halaman interaktif aplikasi.

### 14. Helper styling export mulai duplikatif

Evidence:

- `app/Http/Controllers/ExportController.php`
- `app/Http/Controllers/Admin/KelasSiswaController.php`

Setelah export diperbaiki tampilannya, ada peluang menyatukan style Excel/PDF ke service kecil agar konsisten.

Rekomendasi:

- Buat `app/Services/Export/ExcelStyleFactory.php`.
- Buat template PDF bersama untuk tabel standar.
- Controller hanya mengirim metadata, header, dan rows.

### 15. File tugas lama dapat menjadi orphan

Evidence:

- `app/Http/Controllers/Siswa/TugasController.php`
- `app/Http/Controllers/Guru/TugasController.php`

Ada helper penghapusan file pengumpulan di controller guru, tetapi belum terlihat digunakan menyeluruh. Jika siswa mengubah pengumpulan atau data tugas dihapus, file lama berpotensi tertinggal.

Rekomendasi:

- Pastikan setiap delete/update pengumpulan juga menghapus file lama.
- Tambahkan command audit storage untuk mencari file orphan.
- Tambahkan test penghapusan file saat tugas/pengumpulan dihapus.

## Prioritas Perbaikan 30 Hari

1. Tambahkan fitur wajib ganti password setelah login pertama/reset.
2. Tambahkan unique constraint untuk nilai sikap spiritual dan sosial.
3. Batasi PDF export besar dan ubah Excel export agar memakai chunk/cursor.
4. Pindahkan penyimpanan materi ke disk private.
5. Tambahkan test authorization untuk guru, siswa, dan kepsek.
6. Pecah route berdasarkan role untuk memudahkan maintenance.
7. Tambahkan pruning/redaction untuk system error log.

## Prioritas Perbaikan 90 Hari

1. Buat service khusus untuk laporan dan export.
2. Tambahkan queue untuk export besar.
3. Integrasikan login failure dengan blocked IP.
4. Buat audit log untuk aksi sensitif seperti reset password, import, export, dan perubahan role.
5. Tambahkan dashboard health check backend.
6. Tambahkan dokumentasi arsitektur backend yang menjelaskan boundary controller, service, policy, dan storage.

## Checklist Keamanan Backend

- [ ] User dengan password default dipaksa mengganti password.
- [ ] Reset password tercatat di audit log.
- [ ] Nilai sikap spiritual memiliki unique constraint.
- [ ] Nilai sikap sosial memiliki unique constraint.
- [ ] Export PDF memiliki batas jumlah data.
- [ ] Export Excel memakai chunk/cursor untuk dataset besar.
- [ ] Materi tidak dapat diakses langsung dari path publik.
- [ ] Download file selalu lewat controller dengan authorization.
- [ ] Failed login dicatat.
- [ ] Blocked IP terintegrasi dengan failed login threshold.
- [ ] Error log memiliki redaction dan retention policy.
- [ ] Route sudah dipisah per role.
- [ ] Test role access tersedia untuk admin/guru/siswa/kepsek.

## Catatan Verifikasi

Audit ini dilakukan dengan inspeksi kode lokal. Pada sesi pengerjaan sebelumnya, test backend `php artisan test` berhasil berjalan dengan hasil lulus untuk test yang aktif, dan build frontend juga berhasil. Karena perubahan audit ini hanya menambahkan dokumen, tidak ada perubahan kode runtime yang perlu diuji ulang.
