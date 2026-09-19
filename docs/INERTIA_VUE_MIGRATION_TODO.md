# Inertia + Vue Migration TODO

Dokumen ini adalah roadmap migrasi bertahap dari Laravel Blade ke Laravel + Inertia.js + Vue untuk LMS Sekolah.

Roadmap TypeScript bertahap tersedia di [docs/TYPESCRIPT_MIGRATION_TODO.md](TYPESCRIPT_MIGRATION_TODO.md). TypeScript direncanakan setelah komponen Vue yang ada stabil; tidak ada full rewrite.

Target utama migrasi adalah memperbaiki maintainability dan interaktivitas frontend tanpa mengubah tampilan secara drastis di awal.

## Prinsip Migrasi

- Pertahankan Laravel sebagai backend utama.
- Gunakan Inertia.js agar migrasi tidak perlu langsung membuat REST API besar.
- Gunakan Vue untuk halaman baru dan halaman yang sudah dimigrasi.
- Pertahankan Bootstrap 5, Bootstrap Icons, token warna sekolah, dan visual LMS yang sudah ada.
- Hindari full rewrite sekaligus.
- Migrasikan fitur per role dan per halaman.
- Pastikan setiap fase tetap bisa dirilis.

## Tahap 0 - Audit & Scope

- [x] Inventaris semua halaman Blade aktif berdasarkan role: admin, guru, siswa, kepala sekolah.
- [x] Tandai halaman yang sangat sederhana dan boleh tetap Blade sementara.
- [x] Tandai halaman interaktif prioritas: dashboard, absensi, nilai, kalender, chat, notifikasi.
- [x] Catat semua pemakaian inline script, `@push('scripts')`, `onclick`, Select2, DataTables, dan Chart.js.
- [x] Tentukan komponen UI yang wajib dipertahankan: sidebar, topbar, card, table, badge, form, modal, toast.
- [x] Tentukan halaman pilot pertama untuk migrasi.

Catatan awal:

- Project saat ini memakai Blade server-rendered.
- Ada sekitar 100 file Blade dan sekitar 67 halaman utama berdasarkan role.
- Controller saat ini mayoritas memakai `return view(...)`.
- Frontend awal memakai Bootstrap, Alpine.js, jQuery, Select2, DataTables, dan Chart.js; Alpine.js sudah dilepas pada Tahap 13.

Hasil audit Tahap 0:

- Halaman role utama yang aktif: admin 21 file, guru 24 file, siswa 12 file, kepala sekolah 10 file.
- Komponen Blade reusable yang sudah ada: `x-page-header`, `x-card`, `x-stat-card`, `x-badge`, `x-button`, `x-table-wrapper`, `x-empty-state`, `x-action-buttons`, dan komponen form.
- Layout utama berada di `resources/views/layouts/app.blade.php` dan masih menjadi pusat topbar, sidebar, token warna dinamis, notifikasi topbar, toast, confirm dialog, dan fallback CDN.
- App JS utama berada di `resources/js/app.ts` dan memuat helper sidebar Blade legacy, toast, confirm dialog, loading submit, dan lazy Chart.js.
- CSS aplikasi utama berada di `public/css/lms-app.css`; token warna dinamis masih disuntik dari Blade layout.

Inventaris halaman per role:

- Admin: dashboard, users, kelas, kelas-siswa, mata-pelajaran, kelas-mapel, tahun-ajaran, pengumuman, kalender, school-settings, pengaturan, blocked-ips, log-login, log-error, dan rekap absensi/nilai/sikap/tugas.
- Guru: dashboard, absensi, materi, tugas, nilai, sikap, wali-kelas, kalender, chat, notifikasi, profil, rekap nilai, dan rekap sikap.
- Siswa: dashboard, materi, tugas, nilai, progress, kalender, chat, notifikasi, dan profil.
- Kepala sekolah: dashboard, kalender, statistik, laporan absensi/nilai/rekap/wali-kelas.

Halaman yang boleh tetap Blade sementara:

- Export PDF/Excel dan print report karena outputnya server-side.
- Log login, log error, blocked IP, rekap admin, dan laporan kepala sekolah yang dominan baca data.
- Halaman error/maintenance.
- Login boleh ditunda sampai app shell Inertia stabil.

Halaman interaktif prioritas:

- Dashboard admin, guru, siswa, dan kepala sekolah.
- Absensi guru dan wali kelas.
- Input nilai dan input sikap guru.
- Kalender admin, guru, siswa, dan kepala sekolah.
- Chat guru dan siswa.
- Notifikasi guru dan siswa.
- Tugas dan upload pengumpulan siswa.

Catatan script dan plugin:

- `@push('scripts')` ditemukan pada statistik kepala sekolah, dashboard kepala sekolah, progress siswa, chat siswa, input nilai guru, absensi guru, dan absensi wali kelas.
- Chart.js dipakai melalui `window.renderChart()` pada statistik/dashboard/progress.
- Select2 sebelumnya dipakai pada `admin/kelas-mapel/index.blade.php` untuk pilihan kelas, mapel, dan guru; pemakaian itu sudah diganti ke select Bootstrap biasa pada Tahap 13.
- DataTables sebelumnya tersedia di dependency dan fallback CDN, tetapi audit view tidak menemukan halaman aktif dengan class `.datatable`; dependency dan fallback sudah dihapus pada Tahap 13.
- Inline `onclick` aktif ditemukan pada tombol cetak rekap admin dan halaman maintenance.
- Endpoint JSON khusus chat sudah ada pada `Guru\ChatController` dan `Siswa\ChatController`.

Komponen UI yang wajib dipertahankan saat migrasi:

- App shell: sidebar, topbar, topbar context, account menu, notifikasi topbar.
- Navigasi role: admin, guru, siswa, kepala sekolah.
- Theme tokens: warna sekolah, logo, favicon, nama sekolah, radius, shadow, status color.
- Komponen konten: page header, stat card, card, table wrapper, empty state, badge, action buttons.
- Komponen form: input, textarea, select, file input, section, error message, loading submit.
- Feedback UI: toast, confirm dialog, alert, pagination, chart container.

Pilot migration yang disarankan:

- Pilihan utama: `admin/dashboard`.
- Alasan: hanya baca data, tidak ada form submit, tidak ada upload, tidak memakai script halaman khusus, dan struktur visualnya mewakili stat card + table + list.
- Alternatif kedua: `siswa/dashboard`.
- Alasan: sudah memakai beberapa komponen Blade modern dan tetap relatif aman, tetapi ada logic status tugas dan notifikasi yang sedikit lebih dekat ke data user.
- Jangan mulai dari: absensi, nilai, kalender, chat, upload tugas, atau laporan besar karena risiko regresinya lebih tinggi.

## Tahap 1 - Fondasi Inertia + Vue

- [x] Install dependency backend Inertia Laravel.
- [x] Install dependency frontend Vue, Inertia Vue adapter, dan plugin Vite Vue.
- [x] Update `vite.config.js` agar mendukung Vue.
- [x] Buat entrypoint Vue baru di `resources/js/inertia.ts`.
- [x] Buat root Blade untuk Inertia, misalnya `resources/views/app.blade.php`.
- [x] Tambahkan middleware Inertia untuk shared props.
- [x] Share data global: user login, role, nama sekolah, logo, favicon, tema warna, flash message.
- [x] Pastikan asset Vite build berhasil.
- [x] Pastikan halaman Blade lama tetap berjalan.

Definition of done:

- Inertia aktif tanpa mematahkan halaman Blade lama.
- Satu route test Inertia bisa dibuka.
- `npm run build` berhasil.
- `php artisan view:cache` berhasil.

Catatan Tahap 1:

- Dependency backend `inertiajs/inertia-laravel` sudah dipasang.
- Dependency frontend `@inertiajs/vue3`, `vue`, dan `@vitejs/plugin-vue` sudah dipasang.
- `@vitejs/plugin-vue` dikunci ke versi `5.2.4` karena versi terbaru membutuhkan API Node yang tidak tersedia pada Node 19 di environment ini.
- Middleware `App\Http\Middleware\HandleInertiaRequests` ditambahkan ke web middleware stack setelah `CheckBlockedIp`.
- Shared props awal tersedia untuk `auth.user`, `flash`, `school`, dan `theme`.
- Root view Inertia tersedia di `resources/views/app.blade.php` dan tetap memakai token warna sekolah, favicon, Google Font, Bootstrap CSS bundle, dan `public/css/lms-app.css`.
- Entry point Vue tersedia di `resources/js/inertia.ts`.
- Route test admin tersedia di `/admin/inertia-test` dengan nama route `admin.inertia-test`.
- Page test tersedia di `resources/js/Pages/InertiaTest.vue`.
- Halaman Blade lama tetap memakai `resources/js/app.ts`; halaman Inertia memakai `resources/js/inertia.ts`, sehingga migrasi masih hybrid.
- `php artisan route:list --name=admin.inertia-test`, `php artisan view:cache`, dan `npm run build` berhasil.

## Tahap 2 - App Shell Vue

- [x] Buat layout Vue utama untuk aplikasi login: `AuthenticatedLayout`.
- [x] Migrasikan struktur topbar dari Blade ke Vue.
- [x] Migrasikan struktur sidebar dari Blade ke Vue.
- [x] Buat menu sidebar berdasarkan role dari shared props.
- [x] Pertahankan warna tema dinamis dari pengaturan sekolah.
- [x] Buat komponen global untuk toast.
- [x] Buat komponen global untuk confirm dialog.
- [x] Buat loading indicator untuk navigasi Inertia.
- [x] Pastikan mobile sidebar tetap nyaman.

Komponen kandidat:

- [x] `AppShell.vue`
- [x] `Topbar.vue`
- [x] `Sidebar.vue`
- [x] `SidebarLink.vue`
- [x] `ToastStack.vue`
- [x] `ConfirmDialog.vue`
- [x] `PageHeader.vue`

Definition of done:

- Layout Vue terlihat sangat mirip layout Blade.
- Navigasi role tetap sama.
- User tidak merasa tampilan berubah drastis.

Catatan Tahap 2:

- Layout utama dibuat sebagai `resources/js/Layouts/AppShell.vue`. Nama file mengikuti tujuan app shell; secara fungsi ini adalah authenticated layout untuk halaman Inertia yang sudah login.
- Komponen topbar, sidebar, sidebar link, page header, toast, confirm dialog, dan loading navigasi dibuat di `resources/js/Components/AppShell`.
- Halaman test `resources/js/Pages/InertiaTest.vue` sekarang memakai `AppShell`, sehingga route `/admin/inertia-test` sudah menampilkan topbar/sidebar Vue penuh.
- Menu sidebar dibuat berdasarkan role melalui `sidebarMenu.js`; role admin, guru, siswa, dan kepala sekolah sudah disiapkan.
- Middleware Inertia membagikan data tambahan untuk shell: `capabilities.has_wali_kelas` dan `notifications` untuk role guru/siswa.
- Topbar Vue mendukung account menu, logout POST, link profil guru/siswa, notifikasi terbaru, mark read, dan mark all read.
- Sidebar mobile memakai class CSS lama (`sidebar-open` dan `sidebar-overlay`) agar perilakunya konsisten dengan Blade.
- Toast Vue mengekspos `window.showToast()` agar pola global lama tetap tersedia pada halaman Inertia.
- Confirm dialog Vue mengekspos `window.confirmAction()` dan `window.confirmDialog()` untuk kompatibilitas bertahap.
- Loading indicator Inertia dibuat melalui listener `router.on('start')` dan `router.on('finish')`.
- `npm run build`, `php artisan view:cache`, `php artisan test`, dan `git diff --check` berhasil setelah Tahap 2.

## Tahap 3 - Komponen UI Dasar

- [x] Buat komponen `Card`.
- [x] Buat komponen `StatCard`.
- [x] Buat komponen `Badge`.
- [x] Buat komponen `Button`.
- [x] Buat komponen `IconButton`.
- [x] Buat komponen `EmptyState`.
- [x] Buat komponen `TableWrapper`.
- [x] Buat komponen form: `TextInput`, `TextareaInput`, `SelectInput`, `FileInput`, `InputError`.
- [x] Buat komponen pagination untuk data Laravel paginator.
- [x] Pastikan class Bootstrap dan CSS lama masih bisa dipakai.

Definition of done:

- Komponen Vue setara dengan komponen Blade yang sudah ada.
- Halaman hasil migrasi tidak perlu mengulang markup dasar.

Catatan Tahap 3:

- Komponen UI dasar dibuat di `resources/js/Components/UI`.
- Komponen form dibuat di `resources/js/Components/Form`.
- Komponen UI yang tersedia: `Card`, `StatCard`, `Badge`, `Button`, `IconButton`, `EmptyState`, `TableWrapper`, dan `Pagination`.
- Komponen form yang tersedia: `TextInput`, `TextareaInput`, `SelectInput`, `FileInput`, dan `InputError`.
- `Pagination` menerima array `links` dari paginator Laravel/Inertia.
- `Button` mendukung tombol biasa dan link Inertia melalui prop `href`.
- `IconButton` memakai class `btn-icon` yang sudah ada di CSS lama.
- Komponen form mendukung `v-model`, label, required marker, helper text, `aria-describedby`, `aria-invalid`, dan error state Bootstrap.
- File barrel export tersedia di `resources/js/Components/UI/index.ts` dan `resources/js/Components/Form/index.ts`.
- Route test `/admin/inertia-test` diperbarui sebagai showcase kecil untuk memastikan komponen dasar ikut dikompilasi.
- `npm run build`, `php artisan view:cache`, dan `php artisan test` berhasil setelah Tahap 3.

## Tahap 4 - Pilot Migration

Pilih satu halaman yang cukup penting tetapi risikonya tidak terlalu tinggi.

Opsi pilot yang disarankan:

- [x] Admin Dashboard.
- [x] Siswa Dashboard.
- [ ] Guru Dashboard.

Checklist pilot:

- [x] Ubah route halaman pilot dari `return view(...)` ke `Inertia::render(...)`.
- [x] Kirim data yang sama seperti Blade lama sebagai props.
- [x] Buat page Vue di `resources/js/Pages/...`.
- [x] Gunakan `AuthenticatedLayout`.
- [x] Gunakan komponen UI dasar.
- [x] Samakan visual dengan halaman Blade lama.
- [x] Pastikan chart tetap berjalan dengan lazy load Chart.js.
- [x] Pastikan flash message tampil.
- [x] Pastikan akses role tetap sama.

Definition of done:

- Satu halaman produksi berjalan penuh dengan Inertia + Vue.
- Tidak ada regresi data.
- Tampilan masih konsisten dengan halaman lama.

Catatan Tahap 4:

- Pilot yang dipilih: `admin/dashboard`.
- `App\Http\Controllers\Admin\DashboardController@index` sekarang mengembalikan `Inertia::render('Admin/Dashboard', ...)`.
- Data Blade lama dipertahankan sebagai props: `statistik`, `loginTerbaru`, dan `pengumuman`.
- Props `loginTerbaru` dan `pengumuman` dipetakan di controller agar Vue menerima data kecil dan stabil, termasuk tanggal yang sudah diformat.
- Page Vue dibuat di `resources/js/Pages/Admin/Dashboard.vue`.
- Dashboard memakai `AppShell`, `PageHeader`, `StatCard`, `Card`, `Badge`, `TableWrapper`, dan `EmptyState`.
- Visual tetap mengikuti Blade lama: grid statistik, tabel login terbaru, dan daftar pengumuman terbaru.
- Checklist Chart.js ditandai selesai karena halaman pilot admin dashboard tidak memakai chart; lazy Chart.js lama tetap tidak disentuh.
- Akses role tetap memakai route/middleware lama `auth` dan `role:admin`.
- `php -l app/Http/Controllers/Admin/DashboardController.php`, `php artisan route:list --name=admin.dashboard`, `php artisan view:cache`, `npm run build`, dan `php artisan test` berhasil setelah migrasi pilot.

## Tahap 5 - Form & Validasi

- [x] Standarkan submit form memakai `useForm` dari Inertia.
- [x] Pastikan validasi Laravel muncul di komponen input.
- [x] Pastikan old input/state form tetap nyaman setelah gagal validasi.
- [x] Pastikan CSRF dan method spoofing tidak bermasalah.
- [x] Migrasikan flash success/error ke shared props.
- [x] Standarkan loading state tombol submit.
- [x] Standarkan confirm dialog untuk delete/reset/aksi berisiko.
- [x] Pastikan upload file berjalan via Inertia form.

Halaman kandidat:

- [x] Profil guru.
- [x] Profil siswa.
- [x] Admin mata pelajaran.
- [x] Admin kelas.
- [x] Admin tahun ajaran.

Definition of done:

- Form create/update/delete berjalan tanpa full page reload.
- Error validasi tampil konsisten.
- Upload file tetap aman.

Catatan Tahap 5:

- Halaman `admin/mata-pelajaran` dimigrasikan sebagai pembuktian pola form CRUD Inertia.
- `App\Http\Controllers\Admin\MataPelajaranController@index` sekarang memakai `Inertia::render('Admin/MataPelajaran/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Admin/MataPelajaran/Index.vue`.
- Form tambah dan edit memakai `useForm`, termasuk `post`, `put`, `processing`, `errors`, `reset`, dan `clearErrors`.
- Delete memakai `router.delete()` dan confirm dialog global `window.confirmDialog()`.
- Validasi Laravel tetap berada di controller dan error tampil melalui komponen `TextInput`.
- Flash success/error dari redirect tetap tampil lewat shared props dan `ToastStack`.
- Komponen form Vue (`TextInput`, `TextareaInput`, `SelectInput`, `FileInput`) diperbarui agar atribut native seperti `maxlength`, `min`, dan `accept` jatuh ke input asli.
- `App\Http\Controllers\Admin\KelasController@index` sekarang memakai `Inertia::render('Admin/Kelas/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Admin/Kelas/Index.vue`; tambah, edit, dan hapus kelas memakai `useForm`, `router.delete()`, validasi Laravel, loading state, dan confirm dialog.
- `App\Http\Controllers\Admin\TahunAjaranController@index` sekarang memakai `Inertia::render('Admin/TahunAjaran/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Admin/TahunAjaran/Index.vue`; tambah, edit, aktifkan, dan hapus tahun ajaran memakai `useForm`, `router.post()`, `router.delete()`, validasi Laravel, loading state, dan confirm dialog.
- Sidebar admin menandai `/admin/kelas` dan `/admin/tahun-ajaran` sebagai route Inertia.
- Profil guru dan profil siswa sudah dimigrasikan ke Inertia pada tahap lanjutan.
- Upload file aktif sudah berjalan via Inertia form pada materi guru dan pengumpulan tugas siswa memakai `FileInput` serta `forceFormData: true`.
- `php -l` untuk controller admin mata pelajaran/kelas/tahun ajaran, `php artisan route:list --name=admin.kelas`, `php artisan route:list --name=admin.tahun-ajaran`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah Tahap 5 ditutup.

## Tahap 6 - Table, Filter, Pagination

- [x] Tentukan pola table Vue untuk halaman data.
- [x] Gunakan pagination Laravel melalui props.
- [x] Migrasikan filter query menggunakan Inertia `router.get`.
- [x] Pertahankan query string saat filter berubah.
- [x] Ganti kebutuhan DataTables dengan table Vue jika memungkinkan.
- [x] Ganti kebutuhan Select2 dengan searchable select Vue jika diperlukan.
- [x] Pastikan tabel tetap responsive di mobile.
- [x] Pastikan kolom aksi tetap compact.

Halaman kandidat:

- [x] Admin users.
- [x] Admin kelas siswa.
- [x] Admin log login.
- [x] Admin log error.
- [x] Kepala sekolah laporan.

Definition of done:

- Search/filter/pagination terasa lebih halus.
- Tidak ada ketergantungan jQuery untuk halaman yang sudah dimigrasi.

Catatan Tahap 6:

- Halaman `admin/users` dimigrasikan sebagai pembuktian pola table/filter/pagination Inertia.
- `App\Http\Controllers\Admin\UserController@index` sekarang memakai `Inertia::render('Admin/Users/Index', ...)` untuk daftar Guru & Staf.
- Page Vue dibuat di `resources/js/Pages/Admin/Users/Index.vue`.
- Pagination tetap memakai Laravel paginator, dikirim sebagai props `users.links` dan `users.meta`.
- Query string dipertahankan lewat `paginate(...)->withQueryString()`.
- Filter `search` dan `role_id` memakai Inertia `router.get('/admin/users', filters, { preserveState, preserveScroll, replace })`.
- Reset filter menghapus query string dan tetap memakai Inertia navigation.
- Table memakai `TableWrapper`, `Badge`, `IconButton`, `EmptyState`, dan `Pagination`.
- Tombol tambah/edit sengaja memakai anchor normal karena halaman create/edit masih Blade legacy.
- Toggle aktif/nonaktif memakai `router.post()` dan confirm dialog global.
- Delete memakai `router.delete()` dan confirm dialog global.
- Halaman ini tidak memakai DataTables atau Select2, sehingga tidak ada dependency jQuery pada halaman yang sudah dimigrasi.
- Komponen `SearchableSelect` dibuat di `resources/js/Components/Form/SearchableSelect.vue` sebagai pengganti Select2 untuk halaman Vue.
- `SearchableSelect` dipakai pada pilihan relasional panjang: filter kelas/mapel absensi guru, filter laporan kepsek, dan pilihan siswa penanganan wali kelas.
- Select2 legacy sudah dihapus; halaman Blade `admin/kelas-mapel` sekarang memakai select Bootstrap biasa.
- `App\Http\Controllers\Admin\KelasSiswaController@index` sekarang memakai `Inertia::render('Admin/KelasSiswa/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Admin/KelasSiswa/Index.vue`; filter siswa memakai `router.get`, pagination Laravel props, import Excel memakai `FileInput` + `forceFormData`, dan download template tetap native Laravel download.
- Tambah/edit/hapus siswa, reset password, luluskan kelas, dan hapus kelas memakai `useForm`/`router` dengan confirm dialog.
- `App\Http\Controllers\Admin\SystemController@logLogin` sekarang memakai `Inertia::render('Admin/LogLogin/Index', ...)` dengan search query dan pagination.
- `App\Http\Controllers\Admin\SystemController@logError` sekarang memakai `Inertia::render('Admin/LogError/Index', ...)` dengan filter level dan pagination.
- Sidebar admin menandai `/admin/kelas-siswa`, `/admin/log-login`, dan `/admin/log-error` sebagai route Inertia.
- Kepala sekolah laporan sudah selesai dimigrasikan pada Tahap 11 dan memakai table/filter/pagination Vue.
- `php -l` untuk controller admin users/kelas-siswa/system, `php artisan route:list --name=admin.users`, `php artisan route:list --name=admin.kelas-siswa`, `php artisan route:list --name=admin.log`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah Tahap 6 ditutup.

## Tahap 7 - Halaman Guru Prioritas

- [x] Guru Dashboard.
- [x] Guru Absensi index.
- [x] Guru Absensi create.
- [x] Guru Nilai index.
- [x] Guru Nilai input.
- [x] Guru Tugas index/list.
- [x] Guru Pengumpulan Tugas.
- [x] Guru Materi index.
- [x] Guru Materi list.
- [x] Guru Sikap index.
- [x] Guru Sikap input.
- [x] Guru Wali Kelas.
- [x] Guru Notifikasi.
- [x] Guru Kalender.

Catatan risiko:

- Input nilai, absensi, dan sikap memiliki risiko tinggi karena data banyak dan form padat.
- Migrasikan satu fitur sampai selesai sebelum pindah ke fitur guru lain.

Definition of done:

- Workflow harian guru berjalan nyaman di Vue.
- Tidak ada perubahan aturan bisnis.

Catatan Tahap 7:

- Migrasi dimulai dari `guru/dashboard` karena halaman ini read-only dan risikonya lebih rendah daripada absensi/nilai.
- `App\Http\Controllers\Guru\DashboardController@index` sekarang memakai `Inertia::render('Guru/Dashboard', ...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/Dashboard.vue`.
- Data Blade lama dipertahankan sebagai props: `statistik`, `kelasMapel`, `pengumuman`, `notifikasi`, dan `unreadNotifCount`.
- Props dipetakan di controller agar Vue menerima data kecil dan tanggal sudah diformat.
- Dashboard memakai `AppShell`, `PageHeader`, `StatCard`, `Card`, `TableWrapper`, dan `EmptyState`.
- Visual tetap mengikuti Blade lama: stat card, tabel kelas-mapel diampu, panel notifikasi, dan panel pengumuman.
- Akses role tetap memakai route/middleware lama `auth` dan `role:guru`.
- `php -l app/Http/Controllers/Guru/DashboardController.php`, `php artisan route:list --name=guru.dashboard`, `php artisan view:cache`, `npm run build`, dan `php artisan test` berhasil setelah migrasi Guru Dashboard.
- `guru/materi` dimigrasikan sebagai halaman index kelas-mapel materi yang aman dan read-only.
- `App\Http\Controllers\Guru\MateriController@index` sekarang memakai `Inertia::render('Guru/Materi/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/Materi/Index.vue`.
- `php -l app/Http/Controllers/Guru/MateriController.php`, `php artisan route:list --name=guru.materi`, `php artisan view:cache`, `npm run build`, dan `php artisan test` berhasil setelah migrasi Guru Materi index.
- `guru/materi/{kelasMapel}/list` dimigrasikan ke Inertia.
- `App\Http\Controllers\Guru\MateriController@list` sekarang memakai `Inertia::render('Guru/Materi/List', ...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/Materi/List.vue`.
- Form upload materi memakai `useForm().post()` dengan `forceFormData` ke route lama `guru.materi.store`.
- Download materi tetap memakai route Laravel biasa, sedangkan hapus materi memakai `router.delete()` dan confirm dialog global.
- `php -l app/Http/Controllers/Guru/MateriController.php`, `php artisan route:list --name=guru.materi`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Guru Materi list.
- `guru/sikap` dimigrasikan sebagai halaman index kelas-mapel sikap yang aman dan read-only.
- `App\Http\Controllers\Guru\SikapController@index` sekarang memakai `Inertia::render('Guru/Sikap/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/Sikap/Index.vue`.
- Link kartu sikap tetap menuju route `guru.sikap.input` yang masih Blade legacy, karena halaman input sikap adalah form padat dan akan dimigrasikan terpisah.
- `php -l app/Http/Controllers/Guru/SikapController.php`, `php artisan route:list --name=guru.sikap`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Guru Sikap index.
- `guru/sikap/{kelasMapel}/input` dimigrasikan ke Inertia sebagai form padat sikap spiritual dan sosial.
- `App\Http\Controllers\Guru\SikapController@input` sekarang memakai `Inertia::render('Guru/Sikap/Input', ...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/Sikap/Input.vue`.
- Struktur request tetap sama dengan Blade lama: `semester`, `spiritual[siswa_id][field]`, dan `sosial[siswa_id][field]`, sehingga `SikapController@store` tidak perlu diubah.
- Rata-rata spiritual dan sosial dihitung reaktif di Vue dari pilihan skala 1-5 yang sedang aktif.
- `php -l app/Http/Controllers/Guru/SikapController.php`, `php artisan route:list --name=guru.sikap`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Guru Sikap input.
- Workflow `guru/wali-kelas` dimigrasikan ke Inertia: index, absensi harian, pertemuan, dan penanganan siswa.
- `App\Http\Controllers\Guru\WaliKelasController@index`, `absensi`, `pertemuan`, dan `penanganan` sekarang memakai `Inertia::render(...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/WaliKelas/Index.vue`, `Absensi.vue`, `Pertemuan.vue`, dan `Penanganan.vue`.
- Form absensi wali kelas tetap memakai struktur request lama: `bulan` dan `absensi[siswa_id][tanggal]`, sehingga `storeAbsensi` tidak perlu diubah.
- Fitur isi cepat absensi per kolom tanggal dipindahkan dari inline script Blade ke fungsi Vue `fillColumn`.
- Form pertemuan tetap memakai `useForm().post()` ke route lama `guru.wali-kelas.pertemuan.store`, dan hapus memakai `router.delete()`.
- Form penanganan siswa mendukung tambah, edit inline, dan hapus dengan route Laravel lama melalui Inertia.
- Pagination pertemuan dan penanganan memakai komponen Vue `Pagination`.
- `php -l app/Http/Controllers/Guru/WaliKelasController.php`, `php artisan route:list --name=guru.wali-kelas`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Guru Wali Kelas.
- `guru/notifikasi` dimigrasikan ke Inertia.
- `App\Http\Controllers\Guru\NotifikasiController@index` sekarang memakai `Inertia::render('Guru/Notifikasi/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/Notifikasi/Index.vue`.
- Aksi tandai satu notifikasi dan tandai semua dibaca tetap memakai route lama melalui `router.post()`.
- Pagination notifikasi memakai komponen Vue `Pagination`.
- `php -l app/Http/Controllers/Guru/NotifikasiController.php`, `php artisan route:list --name=guru.notifikasi`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Guru Notifikasi.
- `guru/kalender` dimigrasikan ke Inertia.
- `App\Http\Controllers\Guru\KalenderController@index` sekarang memakai `Inertia::render('Guru/Kalender/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/Kalender/Index.vue`.
- Navigasi bulan memakai URL query lama `year` dan `month`, sedangkan tambah/edit/hapus event pribadi memakai route Laravel lama melalui Inertia.
- Event sekolah tetap read-only untuk guru; event pribadi bisa diedit dan dihapus.
- `php -l app/Http/Controllers/Guru/KalenderController.php`, `php artisan route:list --name=guru.kalender`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Guru Kalender.
- `guru/absensi` dimigrasikan sebagai halaman index absensi dengan filter kelas-mapel dan bulan.
- `App\Http\Controllers\Guru\AbsensiController@index` sekarang memakai `Inertia::render('Guru/Absensi/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/Absensi/Index.vue`.
- Form filter memakai Inertia `router.get()`, sedangkan simpan absensi tetap memakai route lama `guru.absensi.store` melalui `useForm().post()`.
- Struktur data absensi mingguan dipetakan di controller agar Vue menerima `weeks`, `students`, dan status awal per siswa.
- Fitur isi cepat per kolom minggu (`fillColumn`) dipindahkan dari inline script Blade ke fungsi Vue.
- Styling attendance select dipindahkan ke scoped style Vue.
- `guru.absensi.create` ditandai selesai tanpa page Vue terpisah karena route tersebut memang hanya redirect ke index dengan `kelas_mapel_id`; setelah index menjadi Inertia, create otomatis membuka halaman Vue absensi terpilih.
- Audit route menunjukkan tidak ada view atau link lain yang memakai `guru.absensi.create` sebagai halaman mandiri.
- `php -l app/Http/Controllers/Guru/AbsensiController.php`, `php artisan route:list --name=guru.absensi`, `php artisan view:cache`, `npm run build`, dan `php artisan test` berhasil setelah migrasi Guru Absensi index.
- `guru/nilai` dimigrasikan sebagai halaman index kelas-mapel nilai yang aman dan read-only.
- `App\Http\Controllers\Guru\NilaiController@index` sekarang memakai `Inertia::render('Guru/Nilai/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/Nilai/Index.vue`.
- Link kartu nilai tetap menuju route `guru.nilai.input` yang masih Blade legacy, karena halaman input nilai adalah form padat dan akan dimigrasikan terpisah.
- `php -l app/Http/Controllers/Guru/NilaiController.php`, `php artisan route:list --name=guru.nilai`, `php artisan view:cache`, `npm run build`, dan `php artisan test` berhasil setelah migrasi Guru Nilai index.
- `guru/nilai/{kelasMapel}/input` dimigrasikan ke Inertia sebagai form padat pertama untuk workflow guru.
- `App\Http\Controllers\Guru\NilaiController@input` sekarang memakai `Inertia::render('Guru/Nilai/Input', ...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/Nilai/Input.vue`.
- Form nilai memakai `useForm().post()` ke route lama `guru.nilai.store`, sehingga validasi dan proses simpan tetap berada di Laravel.
- Data siswa, nilai awal, tahun ajaran, semester, dan route simpan dipetakan di controller sebagai props kecil.
- Auto-tab setelah input 3 digit dan select-on-focus dipindahkan dari inline script Blade ke handler Vue.
- Field `nilai_harian` ikut ditampilkan di Vue karena controller store dan model sudah mendukung kolom tersebut.
- Nilai rata-rata akhir tetap menampilkan nilai tersimpan dari database, sama seperti Blade lama.
- `php -l app/Http/Controllers/Guru/NilaiController.php`, `php artisan route:list --name=guru.nilai`, `php artisan view:cache`, `npm run build`, dan `php artisan test` berhasil setelah migrasi Guru Nilai input.
- `guru/tugas` dan `guru/tugas/{kelasMapel}/list` dimigrasikan ke Inertia.
- `App\Http\Controllers\Guru\TugasController@index` sekarang memakai `Inertia::render('Guru/Tugas/Index', ...)`.
- `App\Http\Controllers\Guru\TugasController@list` sekarang memakai `Inertia::render('Guru/Tugas/List', ...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/Tugas/Index.vue` dan `resources/js/Pages/Guru/Tugas/List.vue`.
- Halaman index tetap berupa kartu kelas-mapel, sedangkan halaman list memakai form `useForm().post()` untuk membuat tugas baru.
- Delete tugas memakai `router.delete()` dan confirm dialog global.
- `php -l app/Http/Controllers/Guru/TugasController.php`, `php artisan route:list --name=guru.tugas`, `php artisan view:cache`, `npm run build`, dan `php artisan test` berhasil setelah migrasi Guru Tugas index/list.
- `guru/tugas/{kelasMapel}/{tugas}/pengumpulan` dimigrasikan ke Inertia.
- `App\Http\Controllers\Guru\TugasController@pengumpulan` sekarang memakai `Inertia::render('Guru/Tugas/Pengumpulan', ...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/Tugas/Pengumpulan.vue`.
- Row tabel pengumpulan dipisah ke `resources/js/Pages/Guru/Tugas/Partials/SubmissionRow.vue`.
- Form nilai per pengumpulan dipisah ke `resources/js/Pages/Guru/Tugas/Partials/SubmissionGradeForm.vue` dan tetap memakai `useForm().post()` ke route lama `guru.tugas.nilai`.
- Download file pengumpulan tetap memakai route Laravel biasa, sehingga tidak mengubah workflow file.
- Detail pengumpulan memakai modal overlay Vue untuk menggantikan modal Bootstrap Blade.
- `php -l app/Http/Controllers/Guru/TugasController.php`, `php artisan route:list --name=guru.tugas`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Guru Pengumpulan Tugas.

## Tahap 8 - Halaman Siswa Prioritas

- [x] Siswa Dashboard.
- [x] Siswa Materi.
- [x] Siswa Tugas index/show.
- [x] Upload pengumpulan tugas.
- [x] Siswa Nilai.
- [x] Siswa Progress.
- [x] Siswa Kalender.
- [x] Siswa Notifikasi.
- [x] Siswa Profil.

Definition of done:

- Siswa bisa belajar, membuka materi, mengumpulkan tugas, dan melihat nilai tanpa regresi.

Catatan Tahap 8:

- Migrasi dimulai dari `siswa/dashboard` karena halaman ini read-only dan aman sebagai pilot role siswa.
- `App\Http\Controllers\Siswa\DashboardController@index` sekarang memakai `Inertia::render('Siswa/Dashboard', ...)`.
- Page Vue dibuat di `resources/js/Pages/Siswa/Dashboard.vue`.
- Data dashboard dipetakan sebagai props kecil: `stats`, `tugasTerbaru`, `notifikasi`, `pengumuman`, dan `links`.
- `php -l app/Http/Controllers/Siswa/DashboardController.php`, `php artisan route:list --name=siswa.dashboard`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Siswa Dashboard.
- `siswa/materi` dan `siswa/materi/{kelasMapel}` dimigrasikan ke Inertia.
- `App\Http\Controllers\Siswa\MateriController@index` sekarang memakai `Inertia::render('Siswa/Materi/Index', ...)`.
- `App\Http\Controllers\Siswa\MateriController@list` sekarang memakai `Inertia::render('Siswa/Materi/List', ...)`.
- Page Vue dibuat di `resources/js/Pages/Siswa/Materi/Index.vue` dan `resources/js/Pages/Siswa/Materi/List.vue`.
- Download materi tetap memakai route Laravel biasa agar file binary tidak lewat Inertia.
- `php -l app/Http/Controllers/Siswa/MateriController.php`, `php artisan route:list --name=siswa.materi`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Siswa Materi.
- `siswa/tugas` dan `siswa/tugas/{tugas}` dimigrasikan ke Inertia.
- `App\Http\Controllers\Siswa\TugasController@index` sekarang memakai `Inertia::render('Siswa/Tugas/Index', ...)`.
- `App\Http\Controllers\Siswa\TugasController@show` sekarang memakai `Inertia::render('Siswa/Tugas/Show', ...)`.
- Page Vue dibuat di `resources/js/Pages/Siswa/Tugas/Index.vue` dan `resources/js/Pages/Siswa/Tugas/Show.vue`.
- Form pengumpulan tugas pada halaman show ikut dimigrasikan dan tetap memakai route lama `siswa.tugas.kumpul` dengan `useForm().post()` serta `forceFormData`.
- Download file pengumpulan tetap memakai route Laravel biasa.
- `php -l app/Http/Controllers/Siswa/TugasController.php`, `php artisan route:list --name=siswa.tugas`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Siswa Tugas index/show dan upload pengumpulan tugas.
- `siswa/nilai` dimigrasikan ke Inertia.
- `App\Http\Controllers\Siswa\NilaiController@index` sekarang memakai `Inertia::render('Siswa/Nilai/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Siswa/Nilai/Index.vue`.
- Nilai tetap dikelompokkan per tahun ajaran dan semester seperti Blade lama.
- `php -l app/Http/Controllers/Siswa/NilaiController.php`, `php artisan route:list --name=siswa.nilai`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Siswa Nilai.
- `siswa/progress` dimigrasikan ke Inertia.
- `App\Http\Controllers\Siswa\ProgressController@index` sekarang memakai `Inertia::render('Siswa/Progress', ...)`.
- Page Vue dibuat di `resources/js/Pages/Siswa/Progress.vue`.
- Chart nilai per mata pelajaran dipindahkan dari inline script Blade ke lazy import Chart.js di Vue.
- `php -l app/Http/Controllers/Siswa/ProgressController.php`, `php artisan route:list --name=siswa.progress`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Siswa Progress.
- `siswa/kalender` dimigrasikan ke Inertia.
- `App\Http\Controllers\Siswa\KalenderController@index` sekarang memakai `Inertia::render('Siswa/Kalender/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Siswa/Kalender/Index.vue`.
- Kalender siswa tetap read-only, dengan navigasi bulan memakai query lama `year` dan `month`.
- `php -l app/Http/Controllers/Siswa/KalenderController.php`, `php artisan route:list --name=siswa.kalender`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Siswa Kalender.
- `siswa/notifikasi` dimigrasikan ke Inertia.
- `App\Http\Controllers\Siswa\NotifikasiController@index` sekarang memakai `Inertia::render('Siswa/Notifikasi/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Siswa/Notifikasi/Index.vue`.
- Aksi tandai satu notifikasi dan tandai semua dibaca tetap memakai route lama melalui `router.post()`.
- Pagination notifikasi memakai komponen Vue `Pagination`.
- `php -l app/Http/Controllers/Siswa/NotifikasiController.php`, `php artisan route:list --name=siswa.notifikasi`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Siswa Notifikasi.
- `siswa/profil` dimigrasikan ke Inertia.
- `App\Http\Controllers\Siswa\ProfilController@edit` sekarang memakai `Inertia::render('Siswa/Profil', ...)`.
- Page Vue dibuat di `resources/js/Pages/Siswa/Profil.vue`.
- Informasi siswa tetap read-only, sedangkan form ganti password memakai `useForm().put()` ke route lama `siswa.profil.update`.
- `php -l app/Http/Controllers/Siswa/ProfilController.php`, `php artisan route:list --name=siswa.profil`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Siswa Profil.

## Tahap 9 - Chat & Realtime-Like UX

- [x] Migrasikan guru chat index.
- [x] Migrasikan guru chat show.
- [x] Migrasikan siswa chat index.
- [x] Migrasikan siswa chat show.
- [x] Gunakan submit pesan via Inertia atau endpoint JSON yang sudah ada.
- [x] Pertahankan scroll ke pesan terbaru.
- [x] Tambahkan loading state saat kirim pesan.
- [x] Evaluasi polling ringan atau realtime setelah migrasi dasar stabil.

Definition of done:

- Chat terasa lebih halus daripada Blade lama.
- Pesan baru terkirim tanpa refresh penuh.

Catatan Tahap 9:

- `guru/chat` dan `guru/chat/{kelasMapel}` dimigrasikan ke Inertia.
- `siswa/chat` dan `siswa/chat/{kelasMapel}` dimigrasikan ke Inertia.
- `App\Http\Controllers\Guru\ChatController@index` sekarang memakai `Inertia::render('Guru/Chat/Index', ...)`.
- `App\Http\Controllers\Guru\ChatController@chat` sekarang memakai `Inertia::render('Guru/Chat/Show', ...)`.
- `App\Http\Controllers\Siswa\ChatController@index` sekarang memakai `Inertia::render('Siswa/Chat/Index', ...)`.
- `App\Http\Controllers\Siswa\ChatController@show` sekarang memakai `Inertia::render('Siswa/Chat/Show', ...)`.
- Komponen reusable dibuat di `resources/js/Components/Chat/RoomGrid.vue` dan `resources/js/Components/Chat/ChatRoom.vue`.
- Submit pesan memakai `useForm().post()` ke route lama `guru.chat.send` dan `siswa.chat.send`.
- Scroll ke pesan terbaru dipertahankan melalui `ref` area chat, `onMounted`, dan `watch` jumlah pesan.
- Loading state kirim pesan memakai `form.processing`.
- Polling/realtime belum ditambahkan karena endpoint saat ini sudah cukup untuk migrasi dasar; opsi polling ringan bisa dievaluasi setelah halaman chat dipakai dan kebutuhan refresh otomatis jelas.
- `php -l app/Http/Controllers/Guru/ChatController.php`, `php -l app/Http/Controllers/Siswa/ChatController.php`, `php artisan route:list --name=guru.chat`, `php artisan route:list --name=siswa.chat`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Tahap 9.

## Tahap 10 - Kalender

- [x] Migrasikan kalender admin.
- [x] Migrasikan kalender guru.
- [x] Migrasikan kalender siswa.
- [x] Migrasikan kalender kepala sekolah.
- [x] Buat komponen kalender reusable.
- [x] Standarkan create/update/delete event.
- [x] Pastikan tampilan mobile rapi.

Definition of done:

- Semua role memakai komponen kalender yang sama dengan aturan akses berbeda.

Catatan Tahap 10:

- Kalender admin dimigrasikan ke Inertia melalui `App\Http\Controllers\Admin\KalenderController@index` dan page `resources/js/Pages/Admin/Kalender/Index.vue`.
- Kalender kepala sekolah dimigrasikan ke Inertia melalui `App\Http\Controllers\Kepsek\KalenderController@index` dan page `resources/js/Pages/Kepsek/Kalender/Index.vue`.
- Kalender guru dan siswa yang sudah dimigrasikan sebelumnya direfactor agar memakai komponen kalender reusable yang sama.
- Komponen reusable dibuat di `resources/js/Components/Calendar/CalendarWorkspace.vue`.
- Admin dapat membuat event cakupan `school` atau `user`, serta edit/delete semua event kalender.
- Guru tetap hanya dapat membuat dan mengelola event pribadi; event sekolah tampil read-only.
- Siswa tetap read-only tanpa form create/edit/delete.
- Kepala sekolah mengelola event `school`, termasuk toggle selesai melalui route lama `kepsek.kalender.toggle-done`.
- Navigasi bulan tetap memakai query lama `year` dan `month`.
- `php -l` untuk controller kalender admin/guru/siswa/kepsek, `php artisan route:list --name=admin.kalender`, `php artisan route:list --name=guru.kalender`, `php artisan route:list --name=siswa.kalender`, `php artisan route:list --name=kepsek.kalender`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Tahap 10.

## Tahap 11 - Kepala Sekolah & Laporan

- [x] Kepala sekolah dashboard.
- [x] Statistik.
- [x] Laporan absensi.
- [x] Laporan nilai.
- [x] Laporan rekap absensi.
- [x] Laporan rekap tugas.
- [x] Laporan rekap sikap.
- [x] Laporan wali kelas.
- [x] Pastikan export PDF/Excel tetap route Laravel biasa.

Definition of done:

- Laporan tetap akurat.
- Export tidak ikut dipaksa menjadi Inertia.

Catatan Tahap 11:

- Migrasi menu kepala sekolah dimulai dari dashboard karena read-only dan aman sebagai pilot role kepala sekolah.
- Export PDF/Excel tetap memakai route Laravel GET biasa: `admin.export.nilai.excel`, `admin.export.nilai.pdf`, `admin.export.absensi.excel`, `admin.export.absensi.pdf`, `admin.export.tugas.excel`, dan `admin.export.tugas.pdf`.
- `App\Http\Controllers\ExportController` tidak memakai Inertia; Excel tetap `response()->download(...)`, PDF tetap `Pdf::loadView(...)->download(...)`.
- Link export di Blade admin tetap native `<a href>`. Jika tombol export ditambahkan di halaman Vue nanti, gunakan native `<a>` agar browser menerima file download langsung.
- `App\Http\Controllers\Kepsek\DashboardController@index` sekarang memakai `Inertia::render('Kepsek/Dashboard', ...)`.
- Page Vue dibuat di `resources/js/Pages/Kepsek/Dashboard.vue`.
- Chart absensi 7 hari terakhir dipindahkan dari inline Blade script ke lazy import Chart.js di Vue.
- Sidebar kepala sekolah menandai `/kepsek/dashboard` sebagai route Inertia.
- `php -l app/Http/Controllers/Kepsek/DashboardController.php`, `php artisan route:list --name=kepsek.dashboard`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi dashboard kepala sekolah.
- `kepsek/statistik` dimigrasikan ke Inertia.
- `App\Http\Controllers\Kepsek\StatistikController@index` sekarang memakai `Inertia::render('Kepsek/Statistik/Index', ...)`.
- Page Vue dibuat di `resources/js/Pages/Kepsek/Statistik/Index.vue`.
- Chart siswa per kelas, distribusi nilai, dan tren kehadiran bulanan dipindahkan ke lazy import Chart.js di Vue.
- Sidebar kepala sekolah menandai `/kepsek/statistik` sebagai route Inertia.
- `php -l app/Http/Controllers/Kepsek/StatistikController.php`, `php artisan route:list --name=kepsek.statistik`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Statistik kepala sekolah.
- `kepsek/laporan/absensi` dimigrasikan ke Inertia.
- `App\Http\Controllers\Kepsek\LaporanController@absensi` sekarang memakai `Inertia::render('Kepsek/Laporan/Absensi', ...)`.
- Page Vue dibuat di `resources/js/Pages/Kepsek/Laporan/Absensi.vue`.
- Filter laporan absensi memakai `router.get()` dengan query lama yang sama: `kelas_mapel_id`, `tanggal_awal`, `tanggal_akhir`, dan `status`.
- Pagination laporan absensi memakai komponen Vue `Pagination` dan tetap mempertahankan query filter melalui `withQueryString()`.
- Route export Laravel tidak disentuh.
- Sidebar kepala sekolah menandai `/kepsek/laporan/absensi` sebagai route Inertia.
- `php -l app/Http/Controllers/Kepsek/LaporanController.php`, `php artisan route:list --name=kepsek.laporan.absensi`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Laporan Absensi kepala sekolah.
- `kepsek/laporan/nilai` dimigrasikan ke Inertia.
- `App\Http\Controllers\Kepsek\LaporanController@nilai` sekarang memakai `Inertia::render('Kepsek/Laporan/Nilai', ...)`.
- Page Vue dibuat di `resources/js/Pages/Kepsek/Laporan/Nilai.vue`.
- Filter laporan nilai memakai `router.get()` dengan query lama yang sama: `kelas_id`, `mapel_id`, dan `semester`.
- Pagination laporan nilai memakai komponen Vue `Pagination` dan tetap mempertahankan query filter melalui `withQueryString()`.
- Route export Laravel tidak disentuh.
- Sidebar kepala sekolah menandai `/kepsek/laporan/nilai` sebagai route Inertia.
- `php -l app/Http/Controllers/Kepsek/LaporanController.php`, `php artisan route:list --name=kepsek.laporan.nilai`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Laporan Nilai kepala sekolah.
- `kepsek/laporan/rekap-absensi` dimigrasikan ke Inertia.
- `App\Http\Controllers\Kepsek\LaporanController@rekapAbsensi` sekarang memakai `Inertia::render('Kepsek/Laporan/RekapAbsensi', ...)`.
- Page Vue dibuat di `resources/js/Pages/Kepsek/Laporan/RekapAbsensi.vue`.
- Tampilan rekap per kelas tetap berupa kartu dengan total siswa, total hadir, total absensi, dan progress persentase kehadiran.
- Sidebar kepala sekolah menandai `/kepsek/laporan/rekap-absensi` sebagai route Inertia.
- `php -l app/Http/Controllers/Kepsek/LaporanController.php`, `php artisan route:list --name=kepsek.laporan.rekap-absensi`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Laporan Rekap Absensi kepala sekolah.
- `kepsek/laporan/rekap-tugas` dimigrasikan ke Inertia.
- `App\Http\Controllers\Kepsek\LaporanController@rekapTugas` sekarang memakai `Inertia::render('Kepsek/Laporan/RekapTugas', ...)`.
- Page Vue dibuat di `resources/js/Pages/Kepsek/Laporan/RekapTugas.vue`.
- Filter rekap tugas memakai `router.get()` dengan query lama yang sama: `kelas_id` dan `search`.
- Pagination rekap tugas memakai komponen Vue `Pagination` dan tetap mempertahankan query filter melalui `withQueryString()`.
- Tampilan kartu tugas tetap menampilkan status deadline, total/sudah/belum kumpul, rata-rata nilai, dan progress pengumpulan.
- Sidebar kepala sekolah menandai `/kepsek/laporan/rekap-tugas` sebagai route Inertia.
- `php -l app/Http/Controllers/Kepsek/LaporanController.php`, `php artisan route:list --name=kepsek.laporan.rekap-tugas`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Laporan Rekap Tugas kepala sekolah.
- `kepsek/laporan/rekap-sikap` dimigrasikan ke Inertia.
- `App\Http\Controllers\Kepsek\LaporanController@rekapSikap` sekarang memakai `Inertia::render('Kepsek/Laporan/RekapSikap', ...)`.
- Page Vue dibuat di `resources/js/Pages/Kepsek/Laporan/RekapSikap.vue`.
- Filter rekap sikap memakai `router.get()` dengan query lama `kelas_id`.
- Tabel sikap sosial dan spiritual tetap dipisah seperti Blade lama, dengan ringkasan rata-rata dihitung di Vue.
- Sidebar kepala sekolah menandai `/kepsek/laporan/rekap-sikap` sebagai route Inertia.
- `php -l app/Http/Controllers/Kepsek/LaporanController.php`, `php artisan route:list --name=kepsek.laporan.rekap-sikap`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Laporan Rekap Sikap kepala sekolah.
- `kepsek/laporan/wali-kelas` dan `kepsek/laporan/wali-kelas/{waliKelas}` dimigrasikan ke Inertia.
- `App\Http\Controllers\Kepsek\LaporanController@waliKelas` sekarang memakai `Inertia::render('Kepsek/Laporan/WaliKelas/Index', ...)`.
- `App\Http\Controllers\Kepsek\LaporanController@waliKelasShow` sekarang memakai `Inertia::render('Kepsek/Laporan/WaliKelas/Show', ...)`.
- Page Vue dibuat di `resources/js/Pages/Kepsek/Laporan/WaliKelas/Index.vue` dan `resources/js/Pages/Kepsek/Laporan/WaliKelas/Show.vue`.
- Detail wali kelas mempertahankan filter bulan `bulan`, rekap absensi bulanan per siswa, pertemuan terbaru, dan daftar penanganan siswa.
- Sidebar kepala sekolah menandai `/kepsek/laporan/wali-kelas` sebagai route Inertia.
- `php -l app/Http/Controllers/Kepsek/LaporanController.php`, `php artisan route:list --name=kepsek.laporan.wali-kelas`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Laporan Wali Kelas kepala sekolah.

## Tahap 12 - Login & Guest Pages

- [x] Migrasikan login ke Vue atau pertahankan Blade jika lebih sederhana.
- [x] Pastikan branding sekolah tetap dinamis.
- [x] Pastikan error login tampil konsisten.
- [x] Pastikan redirect berdasarkan role tetap sama.
- [x] Migrasikan halaman maintenance/error hanya jika diperlukan.

Rekomendasi:

- Login boleh dimigrasi setelah app shell stabil.
- Tidak wajib menjadi halaman pertama karena risikonya menyentuh auth flow.

Catatan Tahap 12:

- Login dimigrasikan ke Inertia melalui `App\Http\Controllers\Auth\LoginController@showLogin`.
- Page Vue dibuat di `resources/js/Pages/Auth/Login.vue`.
- Branding sekolah tetap dinamis lewat props `branding`: nama sekolah, nama pendek, motto, alamat, dan logo.
- Submit login tetap memakai route lama `login.post`, sehingga validasi, rate limit, log login, session regenerate, remember me, dan redirect berdasarkan role tetap berada di `LoginController@login`.
- Error validasi memakai `useForm().errors`, sedangkan flash error/success memakai shared Inertia prop `flash`.
- Halaman maintenance/error tetap Blade karena sederhana, tidak membutuhkan state Vue, dan lebih aman untuk kondisi fallback/error.
- Root view Inertia `resources/views/app.blade.php` diberi fallback tema saat DB pengaturan tidak tersedia, mengikuti pola aman di middleware Inertia.
- `php -l app/Http/Controllers/Auth/LoginController.php`, `php artisan route:list --name=login`, `php artisan route:list --name=logout`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Tahap 12.

## Tahap 13 - Cleanup

- [x] Hapus Alpine.js dari halaman yang sudah tidak memakai Blade interaktif.
- [x] Hapus jQuery dari bundle utama jika tidak ada halaman aktif yang membutuhkan.
- [x] Hapus Select2 jika sudah diganti semua.
- [x] Hapus DataTables jika sudah diganti semua.
- [x] Hapus helper JS global yang sudah diganti komponen Vue.
- [x] Rapikan CSS lama yang sudah tidak terpakai.
- [x] Update dokumentasi arsitektur.
- [x] Update panduan instalasi dan build.

Definition of done:

- Bundle frontend lebih bersih.
- Tidak ada dependency lama yang dipertahankan tanpa alasan.

Catatan Tahap 13:

- Alpine.js sudah dihapus dari `resources/js/app.ts`, `resources/views/layouts/app.blade.php`, `package.json`, dan `package-lock.json`; sidebar Blade legacy kini memakai vanilla JS kecil via `data-sidebar-toggle`.
- jQuery sudah dikeluarkan dari bundle utama dan dependency karena tidak ada plugin jQuery aktif setelah Select2/DataTables dihapus.
- Select2 sudah dihapus dari `resources/js/app.ts`, `resources/css/app.css`, fallback CDN layout Blade, `package.json`, dan `package-lock.json`.
- DataTables sudah dihapus dari `resources/js/app.ts`, `resources/css/app.css`, fallback CDN layout Blade, `package.json`, dan `package-lock.json`.
- Helper global `window.showToast()` dan `window.renderChart()` sudah dihapus dari `resources/js/app.ts`; toast dan chart pada halaman Inertia kini ditangani komponen Vue/page Vue masing-masing.
- Container toast Blade legacy `#toastContainer` juga dihapus dari `resources/views/layouts/app.blade.php`.
- Helper legacy `data-confirm` dan loading submit tetap dipertahankan karena masih dipakai halaman Blade aktif.
- CSS lama yang jelas tidak terpakai sudah dirapikan: selector `[x-cloak]` Alpine dan blok `.dataTables_wrapper...` di `public/css/lms-app.css` dihapus.
- Panduan instalasi/build di `README.md` dan `docs/INSTALLATION.md` sudah diperbarui untuk stack Laravel + Inertia/Vue + Vite, Node 20+/22+, `npm run dev`, `npm run build`, dan penghapusan Alpine.js/jQuery/Select2/DataTables.
- Cleanup dependency belum dilakukan penuh karena masih ada halaman Blade aktif yang membutuhkan modal Bootstrap dan helper legacy `data-confirm`/loading submit.
- Error menu admin yang muncul seperti window/modal confirm disebabkan oleh sidebar Vue yang sebelumnya memakai Inertia `<Link>` untuk semua route, termasuk route Blade lama.
- `resources/js/Components/AppShell/sidebarMenu.js` sekarang menandai item menu yang sudah Inertia dengan flag `inertia: true`.
- `resources/js/Components/AppShell/SidebarLink.vue` sekarang memakai `<Link>` hanya untuk route Inertia dan memakai `<a>` normal untuk route Blade lama.
- `resources/js/Components/AppShell/Topbar.vue` sempat membuka profil guru dengan navigasi native saat profil guru masih Blade; setelah migrasi profil guru, profil guru dan siswa sama-sama memakai Inertia.
- `App\Http\Controllers\Auth\LoginController@login` sekarang memakai `Inertia::location()` setelah login Inertia agar redirect ke role yang masih Blade tetap menjadi full page visit.
- `Guru\NotifikasiController@markRead` dan `Siswa\NotifikasiController@markRead` sekarang memakai `Inertia::location()` saat notifikasi dibuka dari request Inertia dan target link bisa berupa halaman Blade lama.
- Dengan strategi ini, halaman Blade lama reload penuh sehingga script Blade lama tetap terinisialisasi, sementara halaman Inertia tetap memakai navigasi SPA.
- `php -l` untuk controller auth/notifikasi yang berubah, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah audit/fix link hybrid.
- `guru/profil` dimigrasikan ke Inertia setelah audit hybrid navigation.
- `App\Http\Controllers\Guru\ProfilController@edit` sekarang memakai `Inertia::render('Guru/Profil', ...)`.
- Page Vue dibuat di `resources/js/Pages/Guru/Profil.vue`.
- Form edit profil guru tetap memakai route lama `guru.profil.update` dengan `useForm().put()`, termasuk password opsional dan validasi `current_password`.
- `php -l app/Http/Controllers/Guru/ProfilController.php`, `php artisan route:list --name=guru.profil`, `php artisan view:cache`, `npm run build`, `php artisan test`, dan `git diff --check` berhasil setelah migrasi Guru Profil.

## Strategi Route

Gunakan strategi hybrid selama migrasi.

- Blade lama tetap memakai `return view(...)`.
- Halaman baru atau sudah dimigrasi memakai `Inertia::render(...)`.
- Link menuju halaman Blade lama harus memakai `<a href="...">` atau navigasi native, bukan Inertia `<Link>`.
- Link menuju halaman Inertia boleh memakai `<Link>`.
- Export, download file, dan PDF tetap response Laravel biasa.
- Endpoint JSON yang sudah ada boleh dipertahankan jika lebih cocok untuk chat atau polling.

Contoh arah struktur:

```text
resources/js/
  app.js
  inertia.js
  Layouts/
  Components/
  Pages/
    Admin/
    Guru/
    Siswa/
    Kepsek/
```

## Urutan Eksekusi Disarankan

1. Install dan aktifkan Inertia + Vue.
2. Buat app shell Vue yang meniru layout Blade.
3. Buat komponen UI dasar.
4. Migrasikan satu dashboard sebagai pilot.
5. Migrasikan form sederhana.
6. Migrasikan table/filter/pagination.
7. Migrasikan fitur guru yang paling sering dipakai.
8. Migrasikan fitur siswa.
9. Migrasikan kalender dan chat.
10. Migrasikan laporan kepala sekolah.
11. Bersihkan dependency lama.

## Risiko Utama

- Full rewrite terlalu cepat bisa membuat banyak bug kecil di role berbeda.
- Form padat seperti nilai dan absensi mudah mengalami regresi.
- jQuery plugin bisa bentrok jika dipakai bersamaan dengan Vue di node yang sama.
- Shared props terlalu besar bisa membuat setiap navigasi berat.
- Redesign bersamaan dengan migrasi teknis bisa memperbesar scope.

## Keputusan yang Perlu Dibuat

- [ ] Apakah semua halaman wajib dimigrasi, atau beberapa halaman laporan tetap Blade?
- [ ] Apakah Bootstrap tetap dipakai penuh, atau mulai pindah ke component styling baru?
- [ ] Apakah login ikut dimigrasi ke Vue?
- [ ] Apakah DataTables diganti total atau dipertahankan untuk halaman tertentu?
- [ ] Apakah chat cukup Inertia submit biasa atau perlu realtime/polling?

## Definition of Done Migrasi Besar

- Semua role utama bisa login dan menjalankan workflow harian.
- Halaman dashboard, absensi, nilai, tugas, materi, kalender, chat, notifikasi, dan laporan berjalan.
- Tampilan tidak berubah drastis tanpa keputusan redesign.
- Semua form penting menampilkan error validasi dengan benar.
- Export PDF/Excel tetap berfungsi.
- Upload file materi dan tugas tetap berfungsi.
- `npm run build` berhasil.
- Test Laravel yang relevan berhasil.
- Dokumentasi arsitektur dan instalasi sudah diperbarui.
