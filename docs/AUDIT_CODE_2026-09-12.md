# Audit Kode Setelah Migrasi TypeScript

Tanggal: 12 September 2026.

## Cakupan

- Cakupan TypeScript seluruh script frontend, import/variabel tidak terpakai,
  komponen form bersama, lifecycle event, navigasi Inertia/Blade, dan penilaian.
- Kontrak dashboard siswa dengan status pengumpulan tugas serta query yang dipakai.
- Tinjauan route/middleware peran, pemeriksaan kepemilikan unduhan/pengumpulan,
  dan hasil suite regresi autentikasi, otorisasi, integritas, serta fitur Laravel.
- Sintaks backend, build produksi, cache Blade, lint proyek, konfigurasi Composer,
  advisory dependensi npm/Composer, dan workflow browser lintas peran.

## Temuan Dan Perbaikan

1. **P1: Paste nilai dapat bergeser ke siswa lain.**
   `SubmissionGradeForm.vue` membuang sel kosong sebelum mendistribusikan nilai.
   Posisi sel sekarang dipertahankan, dengan tes tiga siswa dan sel kosong di tengah.
2. **P1: Autosave bertumpuk dan snapshot tidak sesuai request.**
   Form dapat mengirim request bersamaan, lalu menganggap draf terbaru sudah
   tersimpan ketika respons untuk draf sebelumnya tiba. Request kini berurutan;
   snapshot diambil saat pengiriman dan perubahan berikutnya dijadwalkan ulang.
   Respons untuk komponen yang dilepas/diganti tidak memperbarui state lama.
3. **P2: Tombol simpan nilai valid terhalang validasi browser.**
   Pola HTML menggunakan backslash ganda sehingga angka biasa tidak cocok.
   Pola diganti ke kelas karakter numerik dan diuji dengan angka desimal.
4. **P2: Dashboard siswa menampilkan status tugas yang keliru.**
   Keberadaan record pengumpulan tidak selalu berarti sudah mengumpulkan.
   Status sekarang memakai `STATUS_SUBMITTED`, konsisten dengan angka ringkasan.
   Tautan tugas juga diarahkan ke detail tugas, bukan daftar umum.
5. **P2: Promise konfirmasi dapat menggantung.**
   Konfirmasi berikutnya kini membatalkan promise sebelumnya. Unmount membatalkan
   promise aktif dan menghapus handler global milik komponen tersebut.
6. **P2: Navigasi keyboard komponen bersama tidak konsisten.**
   Command palette kini menahan fokus di dialog, menerima Escape dari tombol menu,
   dan mengembalikan fokus saat ditutup. Searchable select membuka opsi terakhir
   dengan ArrowUp dan mereset indeks setelah pencarian berubah.
7. **P3: Kode dan query yang tidak terpakai.**
   Lima deklarasi/import frontend dibersihkan; `noUnusedLocals` dan
   `noUnusedParameters` diaktifkan. Query statistik/absensi dashboard siswa yang
   hasilnya tidak dikirim ke halaman dihapus. PHP yang disentuh mengikuti Pint.

## Verifikasi

- `php artisan test --colors=never`: 80 test lulus, 754 assertion.
- `composer lint`, Pint untuk PHP yang disentuh, dan `composer validate --strict`: lulus.
- `php artisan view:cache`: lulus.
- Pemeriksaan sintaks file backend di app/routes/tests/database/migrations: lulus.
- `npm audit --json`: 0 vulnerability dilaporkan.
- `composer audit --format=json`: tidak ada advisory atau paket abandoned.
- `npm run typecheck`: lulus, termasuk pemeriksaan unused code.
- `npm run build`: lulus, 852 module diproses.
- `git diff --check`: lulus.
- `npm run test:browser`: 13 test lulus, meliputi 58 halaman lintas empat peran,
  drawer 1440/820/390px, simpan/reload nilai dan absensi, notifikasi, chart,
  navigasi hybrid, autosave tertunda, paste bersel kosong, dan keyboard komponen.

## Batasan

Audit ini bukan jaminan tidak ada bug dan bukan penetration test produksi.
Browser test menggunakan Chromium dengan SQLite temporer, bukan database sekolah.
Perilaku konkurensi database MySQL, beban produksi, serta layanan eksternal tidak
diuji langsung. Browser lokal memakai bypass CSP untuk server HTTP fixture;
pengujian header keamanan Laravel tetap terpisah. Lint PHP proyek mencakup daftar
file dalam script Composer, bukan klaim seluruh PHP lama sudah seragam formatnya.
Perubahan pengguna yang sudah ada dipertahankan; tidak dilakukan refactor massal.
