# LMS Modern UI TODO

Dokumen ini melacak rombakan UI modern untuk demo LMS. V1 sudah diarahkan ke workspace modern dengan shell baru, dashboard role-first, dan komponen reusable.

## V1 Implemented

- AppShell modern dengan sidebar ringkas, topbar compact, dan mobile bottom navigation.
- Dashboard Admin sebagai pusat health operasional.
- Dashboard Guru sebagai teaching cockpit.
- Dashboard Siswa sebagai command center belajar.
- Komponen reusable:
  - `DashboardHero`
  - `MetricStrip`
  - `QuickActionBar`
  - `ActionQueue`
  - `CourseCard`
  - `AgendaPanel`
- Lazy page imports untuk Inertia agar bundle awal lebih ringan.
- Pagination tidak lagi memakai `v-html`.

## Current Checkpoint

Milestone 1 sampai 3 telah diimplementasikan pada domain demo. Workspace kelas/mapel menjadi titik masuk alur guru dan siswa, sedangkan tabel dipertahankan untuk entri serta rekap data yang memang membutuhkan pemindaian kolom.

- `AgendaPanel` dipakai pada ringkasan workspace guru dan siswa untuk deadline tugas yang dapat langsung dibuka.
- `CourseCard` dashboard guru dan siswa memakai data kelas/mapel nyata beserta jumlah materi dan tugas.
- Topbar membuka command palette fungsional untuk navigasi menu melalui klik, `/`, atau `Ctrl/Cmd+K`; ArrowUp, ArrowDown, Enter, dan Escape didukung.
- `SearchableSelect` mendukung keyboard dan atribut ARIA combobox/listbox.
- Materi, Tugas, Nilai, dan Absensi guru telah memiliki konteks workspace; Materi dan Tugas siswa diarahkan kembali ke workspace kelas/mapel.
- Penugasan Guru admin telah memakai halaman Inertia modern dengan metric strip, form searchable, daftar pengajaran, dan daftar wali kelas.
- Kalender lintas peran tetap tersedia pada halaman Kalender masing-masing role; agenda workspace berfokus pada tindakan deadline yang relevan dengan kelas/mapel aktif.

Pekerjaan yang masih memerlukan verifikasi operasional, bukan implementasi tambahan:

- Uji manual desktop dan mobile menggunakan akun Admin, Guru, serta Siswa.
- Uji data kosong, banyak data, dan respons error pada tiap alur utama.
- Jalankan audit dependency saat akses registry tersedia, lalu tinjau perubahan lockfile sebelum menerapkannya.

## Next UI Pass: Ordered Milestones

### Milestone 1: Workspace Kelas/Mapel - Selesai

Jadikan detail kelas/mapel sebagai fondasi navigasi kerja guru dan siswa.

- Buat halaman detail kelas/mapel dengan tabs: Ringkasan, Materi, Tugas, Nilai, Absensi, dan Chat.
- Tentukan akses tab berdasarkan peran serta relasi kelas/mapel pengguna.
- Sajikan ringkasan course yang nyata: guru, kelas, semester, deadline terdekat, tugas belum dinilai, dan notifikasi terbaru.
- Pastikan URL setiap tab dapat dibuka langsung serta mempertahankan konteks kelas/mapel.

### Milestone 2: Modernisasi Alur Harian - Selesai

- Jadikan halaman `Materi`, `Tugas`, `Nilai`, dan `Absensi` berbasis workspace tab/card, bukan tabel sebagai tampilan pertama.
- Pertahankan tabel sebagai mode detail/rekap untuk kebutuhan scan data dan ekspor.
- Prioritaskan alur Guru: membuat materi/tugas, memeriksa pengumpulan, memberi nilai, dan mencatat absensi.
- Prioritaskan alur Siswa: membuka course, melihat deadline, mengumpulkan tugas, serta membaca feedback nilai.

### Milestone 3: Command dan Agenda - Selesai

- Command palette navigasi dari topbar dengan shortcut dan keyboard lengkap.
- Agenda deadline pada workspace dan halaman Kalender lintas peran yang sudah tersedia.
- `SearchableSelect` dengan ArrowDown, ArrowUp, Enter, Escape, serta role combobox/listbox.

## Acceptance Checks Per Milestone

- Uji desktop dan mobile untuk Admin, Guru, dan Siswa; teks, action, dan tabel tidak boleh overflow.
- Uji state kosong, loading, error validasi, dan data berjumlah banyak pada setiap workspace baru.
- Pastikan semua aksi tetap memakai route serta otorisasi Laravel yang sudah ada.
- Jalankan `npm run build`, test PHP terkait, dan uji manual alur utama sebelum melanjutkan ke milestone berikutnya.
- Tambahkan atau perbarui dokumentasi props Inertia saat kebutuhan data baru diperkenalkan.

## Backend/Data Enhancements - Selesai Untuk UI Saat Ini

- Props `courses` dashboard memakai count materi dan tugas tanpa query per kartu.
- Props workspace memuat deadline tugas, status absensi guru hari ini, dan antrean pengumpulan untuk dinilai.
- Command palette dibatasi pada menu yang telah tersedia untuk role dan capability pengguna, sehingga tidak membuka route di luar otorisasi sidebar.
- Kontrak props workspace berada pada controller `Guru/KelasMapelWorkspaceController` dan `Siswa/KelasMapelWorkspaceController`.

## Design System Cleanup

- Pindahkan styling utama dari `public/css/lms-app.css` ke pipeline Vite secara bertahap.
- Kurangi ketergantungan ke selector Bootstrap global untuk layout aplikasi.
- Konsolidasikan token warna di satu tempat agar theme sekolah tetap bisa mengontrol accent tanpa membuat UI kembali terlalu gradient.
- Review contrast dan mobile spacing untuk semua role setelah halaman utama ikut dimodernisasi.
- Pindahkan CSS per komponen atau per halaman terlebih dahulu; hindari memindahkan seluruh stylesheet dalam satu perubahan besar.

## Security/Dependency

- `npm audit fix` telah memperbarui `postcss` beserta dependency transitifnya; audit ulang menunjukkan `0 vulnerabilities`.
- Gunakan Node.js LTS 20 atau 22 untuk development/deployment. Lingkungan lokal saat ini memakai Node 19 yang tidak termasuk rentang engine Vite dan plugin yang digunakan.
- Jalankan `npm audit` secara berkala dan tinjau perubahan lockfile setiap pembaruan dependency.
