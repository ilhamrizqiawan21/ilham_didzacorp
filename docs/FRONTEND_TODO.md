# Frontend Modernization TODO

Dokumen ini adalah roadmap perubahan frontend LMS agar lebih modern, konsisten, dan mudah dirawat tanpa mengubah arsitektur utama Laravel Blade.

## Prinsip Arah

- Tetap gunakan Laravel Blade sebagai rendering utama.
- Pertahankan Bootstrap 5 sebagai fondasi UI.
- Gunakan Vite sebagai asset pipeline utama untuk produksi.
- Tambahkan interaktivitas ringan secara bertahap, bukan migrasi ke SPA.
- Prioritaskan dashboard yang compact, cepat discan, dan nyaman dipakai berulang.

## Tahap 1 - Fondasi Asset

- [x] Pastikan semua halaman produksi memakai asset hasil `npm run build`.
- [x] Kurangi ketergantungan CDN untuk Bootstrap, DataTables, Select2, dan icon.
- [x] Pindahkan inline JavaScript global dari `resources/views/layouts/app.blade.php` ke `resources/js/app.ts`.
- [x] Evaluasi inline CSS theme base; dipertahankan di Blade karena berisi token warna dinamis dari pengaturan sekolah.
- [x] Tambahkan dokumentasi cara build frontend di README atau docs install.
- [x] Audit halaman yang masih load Chart.js via CDN dan tentukan pola lazy-load.

Catatan Tahap 1:

- `npm run build` berhasil dan `public/build/manifest.json` tersedia.
- Bootstrap, Bootstrap Icons, jQuery, DataTables, dan Select2 sudah masuk bundle Vite untuk jalur produksi.
- CDN fallback di layout masih dipertahankan hanya untuk kondisi build belum tersedia.
- Bahasa DataTables tidak lagi mengambil JSON eksternal dari CDN; konfigurasi bahasa dasar dipasang lokal di `resources/js/app.ts`.
- Inline CSS theme base belum dipindah karena nilainya bergantung pada data sekolah dan pengaturan tema dari backend.
- Chart.js masih dimuat per halaman via CDN karena belum menjadi dependency lokal. Pola lanjutannya: tambahkan `chart.js` ke dependency, lalu lazy-load hanya pada halaman dashboard/statistik/progress.

## Tahap 2 - Design Token

- [x] Rapikan CSS variables utama: warna, radius, shadow, spacing, typography.
- [x] Standarkan token untuk status: success, warning, danger, info, muted.
- [x] Standarkan ukuran tombol: default, small, icon-only.
- [x] Standarkan ukuran table, form, card, stat card, dan modal.
- [x] Pastikan tema warna dari pengaturan sekolah tetap kompatibel.
- [x] Tambahkan checklist kontras warna untuk tema hijau, biru-azure, dan biru-aqua.

Catatan Tahap 2:

- Token dinamis tetap berada di `resources/views/layouts/app.blade.php` karena bersumber dari pengaturan sekolah.
- Komponen umum di `public/css/lms-app.css` sekarang memakai token untuk font, surface, status, spacing, card, button, form, table, badge, dan focus ring.
- Markup pagination Laravel/Bootstrap tidak diubah untuk menghindari bentrok Vite + Bootstrap.
- Checklist kontras tema tersedia di `docs/FRONTEND_CONTRAST_CHECKLIST.md`.
- `npm run build` berhasil setelah perubahan.

## Tahap 3 - Blade Components

- [x] Buat atau rapikan komponen `x-page-header`.
- [x] Buat komponen `x-card` untuk header, body, footer, dan actions.
- [x] Buat komponen `x-form.input`, `x-form.select`, `x-form.file`, dan `x-form.error`.
- [x] Buat komponen `x-empty-state` yang konsisten.
- [x] Buat komponen `x-action-buttons` untuk edit, delete, reset, download.
- [x] Migrasikan halaman admin prioritas ke komponen secara bertahap.

Catatan Tahap 3:

- Komponen yang sudah ada dirapikan agar bisa dipakai bertahap tanpa mengubah layout besar.
- Komponen baru ditambahkan: `x-form.file`, `x-form.error`, dan `x-action-buttons`.
- `x-form.input` dan `x-form.select` mendukung `wrapperClass`; `x-form.input` juga mendukung `useOld` untuk form modal/edit.
- Halaman `resources/views/admin/kelas-siswa/index.blade.php` dimigrasikan sebagai contoh pertama.
- `php artisan view:cache`, `npm run build`, dan `git diff --check` berhasil.

## Tahap 4 - Interaktivitas Modern

- [x] Tambahkan Alpine.js untuk state kecil seperti dropdown, modal ringan, sidebar, dan loading submit.
- [x] Ganti inline `onclick` sidebar dengan handler JS/Alpine yang rapi.
- [x] Tambahkan loading state otomatis pada form submit.
- [x] Tambahkan disabled state pada tombol submit saat proses berjalan.
- [x] Standarkan confirm dialog untuk aksi delete, reset password, dan luluskan kelas.
- [x] Pastikan toast global bisa dipanggil dari script halaman.

Catatan Tahap 4:

- Alpine.js ditambahkan sebagai dependency lokal dan dibundle lewat Vite.
- Sidebar mobile sekarang dikontrol oleh `x-data="appShell"` di layout, bukan inline `onclick`.
- Form submit global mendapat loading state dan tombol submit otomatis disabled saat proses berjalan.
- Submit button yang memiliki `name/value` diamankan dengan hidden proxy sebelum disabled.
- Confirm dialog memakai class CSS (`confirm-overlay`, `confirm-dialog`) dan tetap mendukung `data-confirm`.
- `showToast()` tetap tersedia global dari `resources/js/app.ts`.
- `php artisan view:cache`, `npm run build`, dan `git diff --check` berhasil.

## Tahap 5 - Table & Data Density

- [x] Tentukan halaman mana yang memakai DataTables dan mana yang cukup pagination Laravel.
- [ ] Standarkan class table: compact, hover, responsive, action column.
- [ ] Pastikan kolom aksi memakai icon button dengan tooltip atau title.
- [ ] Tambahkan empty state untuk tabel kosong.
- [ ] Pastikan filter table selalu terlihat jelas di mobile.
- [ ] Audit overflow tabel nilai, absensi, sikap, dan rekap.

Catatan Tahap 5:

- Saat ini belum ada view yang memakai class `.datatable`; tabel utama tetap memakai pagination Laravel atau tabel statis biasa.
- DataTables tetap tersedia di bundle untuk halaman yang nanti benar-benar butuh sorting/searching client-side tanpa pagination server.
- Standar awal ditambahkan melalui `.app-table`, `.table-action-column`, `.app-table-filter`, dan `x-table-wrapper`.
- Halaman admin prioritas yang mulai dimigrasikan: `admin/kelas`, `admin/mata-pelajaran`, dan `admin/users`.
- `admin/kelas` dan `admin/mata-pelajaran` sekarang memakai `x-card`, `x-form.*`, `x-table-wrapper`, `x-action-buttons`, dan `x-empty-state`.
- `admin/users` sekarang memakai filter responsif, action column icon-only dengan title/aria-label, dan empty state konsisten.
- `php artisan view:cache`, `npm run build`, dan `git diff --check` berhasil setelah perubahan awal Tahap 5.

## Tahap 6 - Form UX

- [x] Standarkan validasi error di bawah field.
- [x] Tambahkan helper text hanya untuk field yang memang perlu penjelasan.
- [x] Gunakan Select2 hanya untuk select panjang atau relasional.
- [x] Pastikan upload file menampilkan ekstensi dan batas ukuran.
- [x] Tambahkan pola form section untuk halaman yang banyak input.
- [x] Audit form import Excel agar template, upload, dan error baris mudah dipahami.

Catatan Tahap 6:

- Komponen `x-form.input`, `x-form.select`, dan `x-form.file` sekarang otomatis menambahkan `is-invalid`, `aria-invalid`, dan `aria-describedby` saat ada error.
- Komponen baru ditambahkan: `x-form.textarea` untuk textarea dengan validasi konsisten dan `x-form.section` untuk mengelompokkan form yang lebih panjang.
- `x-form.file` mendukung `accept-label` dan `max-size`, sehingga upload menampilkan format dan batas ukuran secara konsisten.
- Form import Excel di `admin/kelas-siswa` menampilkan format `.xlsx`, batas 2MB, link template, dan error import baris tetap terlihat di atas form.
- Upload file di `guru/materi/list` dan `siswa/tugas/show` dimigrasikan ke `x-form.file` dengan format dan batas 20MB.
- Form tambah/edit guru-staf dimigrasikan ke `x-form.section` serta komponen input/select agar error dan helper text seragam.
- Select2 saat ini hanya terdeteksi pada form relasional `admin/kelas-mapel` untuk pilihan kelas, mapel, dan guru; belum ada pemakaian Select2 untuk select pendek biasa.
- `php artisan view:cache`, `npm run build`, dan `git diff --check` berhasil setelah perubahan Tahap 6.

## Tahap 7 - Mobile & Responsive

- [x] Audit semua dashboard di viewport mobile.
- [x] Pastikan sidebar drawer tidak menutup topbar secara aneh.
- [x] Pastikan topbar title tidak bertabrakan dengan account menu.
- [x] Buat tombol aksi tabel menjadi compact di mobile.
- [x] Pastikan card stat dan chart tidak overflow.
- [x] Pastikan form filter memakai layout stacked yang rapi di mobile.

Catatan Tahap 7:

- Audit markup/CSS dilakukan pada dashboard admin, guru, siswa, dan kepala sekolah, termasuk area stat card, tabel ringkasan, notifikasi, dan chart.
- `body` sekarang mencegah overflow horizontal global; tabel tetap scroll horizontal lewat `.table-responsive`.
- Sidebar mobile tetap berada di bawah topbar dengan `top: var(--topbar-height)` dan overlay juga dimulai dari bawah topbar.
- Topbar mobile diperkuat dengan judul ellipsis, actions tidak menyusut, notification dropdown dibatasi lebar viewport, dan elemen kecil dipadatkan pada viewport sempit.
- Stat card mobile memakai grid 2 kolom pada tablet/mobile sedang dan 1 kolom pada viewport kecil, dengan ukuran ikon/angka yang lebih stabil.
- Canvas/chart diberi batas lebar dan tinggi minimum pada mobile agar tidak meluber dari card.
- Form filter di card actions dan row filter `align-items-end` distack penuh di mobile; tombol filter dibuat full-width.
- Kolom aksi tabel di mobile dibuat lebih compact dengan icon button 28px dan gap kecil.
- `php artisan view:cache`, `npm run build`, dan `git diff --check` berhasil setelah perubahan Tahap 7.

## Tahap 8 - Accessibility

- [x] Tambahkan `aria-label` untuk semua icon-only button.
- [x] Pastikan focus state jelas untuk link, tombol, input, dan select.
- [x] Pastikan modal confirm bisa ditutup dengan keyboard.
- [x] Pastikan warna badge tidak menjadi satu-satunya penanda status.
- [x] Pastikan heading tiap halaman berurutan dan jelas.
- [x] Audit contrast text pada sidebar, topbar, badge, dan alert.

Catatan Tahap 8:

- Layout sekarang memiliki skip link ke `#mainContent`, `main` landmark, dan navigasi sidebar dengan `aria-label`.
- Focus state global ditambahkan untuk link, tombol, input, select, textarea, pagination, dropdown item, dan nav link melalui `:focus-visible`.
- Confirm dialog sekarang memiliki `aria-describedby`, bisa ditutup dengan Escape, menahan Tab di dalam dialog, dan mengembalikan fokus ke pemicu setelah ditutup.
- Toast global sekarang memakai `aria-live`, `role="status"` untuk info/sukses/peringatan, dan `role="alert"` untuk error.
- Komponen `x-button`, `x-action-buttons`, `x-card`, `x-stat-card`, `x-badge`, dan `x-page-header` menandai ikon dekoratif dengan `aria-hidden`.
- `x-page-header` memakai `h1`; CSS tetap mendukung halaman lama yang masih memakai `h4` agar migrasi bisa bertahap.
- Tombol icon-only prioritas diberi `title` dan `aria-label`: aksi materi, tugas, notifikasi, chat, simpan nilai, reset filter, dan beberapa filter pencarian.
- Badge komponen mendukung `label`; badge manual di halaman lama masih menampilkan teks status langsung sehingga status tidak hanya bergantung pada warna.
- `php artisan view:cache`, `npm run build`, dan `git diff --check` berhasil setelah perubahan Tahap 8.

## Tahap 9 - Performance

- [x] Pastikan CSS dan JS hasil build sudah minified.
- [x] Lazy-load Chart.js hanya pada halaman statistik/progress.
- [x] Hindari inisialisasi DataTables global jika tidak ada `.datatable`.
- [x] Hindari query notifikasi berat di layout jika halaman tidak membutuhkan.
- [x] Optimalkan ukuran logo sekolah dengan batas dimensi dan format.
- [x] Cek bundle size setelah dependensi frontend dirapikan.

Catatan Tahap 9:

- `chart.js` ditambahkan sebagai dependency lokal dan tidak lagi dimuat dari CDN pada halaman `siswa/progress`, `kepsek/dashboard`, dan `kepsek/statistik`.
- Chart.js sekarang dimuat lewat dynamic import `window.renderChart()`, sehingga chunk chart hanya diambil pada halaman yang memanggil chart.
- Select2 dan DataTables tidak lagi masuk bundle utama; keduanya dimuat lewat dynamic import hanya jika halaman memiliki `.select2` atau `.datatable`.
- Audit view menunjukkan belum ada halaman yang memakai class `.datatable`; fallback CDN DataTables masih hanya ada di layout untuk kondisi build belum tersedia.
- Query notifikasi topbar sekarang hanya dijalankan untuk role guru/siswa yang benar-benar memiliki route notifikasi.
- Logo dan favicon diberi dimensi eksplisit, `decoding="async"`, lazy loading pada preview pengaturan, dan URL logo/favicon di-cache bersama pengaturan sekolah.
- Hasil `npm run build`: app JS utama sekitar 222.54 kB / 74.93 kB gzip, dengan chunk terpisah `select2` 73.47 kB, `dataTables.bootstrap5` 91.36 kB, dan `chart` 208.24 kB.
- CSS hasil build tetap minified oleh Vite: sekitar 370.95 kB / 51.14 kB gzip.
- `php artisan view:cache`, `npm run build`, `php -l app/Helpers/SchoolSettingHelper.php`, dan `git diff --check` berhasil setelah perubahan Tahap 9.

## Tahap 10 - Halaman Prioritas

- [ ] Login.
- [x] Admin Dashboard.
- [x] Kelas & Siswa.
- [x] Guru Dashboard.
- [ ] Absensi Guru.
- [x] Input Nilai Guru.
- [x] Tugas Guru dan Siswa.
- [x] Materi Guru dan Siswa.
- [x] Progress Siswa.
- [x] Laporan Kepala Sekolah.

Catatan Tahap 10:

- Halaman `admin/dashboard`, `admin/kelas-siswa`, `guru/dashboard`, `guru/nilai/input`, `guru/tugas/*`, `guru/materi/*`, `siswa/progress`, dan laporan kepala sekolah sudah tersedia dan memakai pola layout modern.
- Halaman `auth/login` masih menggunakan CDN eksternal dan inline CSS; perlu dimigrasi ke Vite serta diselaraskan dengan komponen umum.
- Halaman `guru/absensi` ada fungsional, tetapi markup tabel/form masih perlu disesuaikan dengan `x-card`, `x-form.*`, dan styling konsisten.

## Urutan Eksekusi Disarankan

1. Asset pipeline dan pengurangan CDN.
2. Design token dan struktur CSS.
3. Komponen Blade dasar.
4. Modernisasi halaman Kelas & Siswa sebagai contoh pertama.
5. Terapkan pola yang sama ke halaman admin lain.
6. Lanjutkan ke halaman guru yang padat data.
7. Lanjutkan ke halaman siswa dan kepala sekolah.
8. Audit mobile, accessibility, dan performance.

## Definition of Done

- Tidak ada perubahan visual yang merusak flow role admin, guru, siswa, dan kepala sekolah.
- Semua halaman utama tetap responsive.
- Tidak ada tombol aksi tanpa label atau title.
- Tidak ada dependency CDN kritikal di produksi kecuali memang sengaja.
- `npm run build` berhasil.
- Route dan form penting tetap lolos manual test.
