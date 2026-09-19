# PHASE 2 — ADMIN & FRONTEND CONSISTENCY

Tanggal: 2026-08-19  
Branch: `experimental/demo-lms`

## Tujuan

Menstabilkan modul Admin dan menghilangkan ketimpangan frontend pada menu Admin yang sebelumnya masih memakai Blade legacy sementara sebagian besar menu sudah Inertia/Vue.

## Temuan Audit Frontend

`sidebarMenu.js` menunjukkan beberapa menu Admin menggunakan `inertia: false`, yaitu Pengumuman dan empat menu Rekap. Sementara Dashboard, User, Kelas, Kelas & Siswa, Mata Pelajaran, Penugasan Guru, Tahun Ajaran, Kalender, dan Sistem sudah diarahkan ke Inertia. Ini membuat pengalaman navigasi tidak konsisten.

`AppShell.vue` dan resolver Inertia sudah menjadi shell utama frontend. Karena itu menu Admin yang masih legacy dinormalisasi ke shell yang sama.

## Implementasi

- Pengumuman Admin dipindahkan ke `Pages/Admin/Pengumuman/Index.vue`.
- Detail Pengumuman dipindahkan ke `Pages/Admin/Pengumuman/Show.vue`.
- Rekap Absensi, Nilai, Sikap, dan Tugas dipindahkan ke satu frontend terstruktur `Pages/Admin/Rekap.vue` dengan mode berdasarkan tipe laporan.
- Filter kelas, semester, dan bulan (Absensi) dipertahankan.
- Export Excel/PDF dipertahankan melalui route existing.
- Sidebar Admin sekarang menandai seluruh menu Admin utama sebagai Inertia.
- `PengumumanController` sekarang mengirim data melalui Inertia tanpa mengubah authorization audience logic.
- `RekapController` sekarang mengirim data melalui Inertia tanpa mengubah query bisnis utama.
- Regression test ditambahkan untuk memastikan lima menu legacy tersebut benar-benar merender component Inertia yang benar.

## Residu

Tidak menghapus Blade legacy pada phase ini karena masih perlu audit referensi lintas role. Penghapusan dilakukan setelah seluruh role yang berbagi controller/view telah dikonversi dan tidak ada referensi valid yang tersisa.

## Acceptance

- [x] Semua menu Admin utama memakai pola frontend yang konsisten.
- [x] Pengumuman Admin memakai Inertia.
- [x] Detail Pengumuman memakai Inertia.
- [x] Empat halaman Rekap Admin memakai Inertia.
- [x] Filter Rekap dipertahankan.
- [x] Export Rekap dipertahankan.
- [x] Authorization audience Pengumuman dipertahankan.
- [x] Regression test frontend ditambahkan.
- [x] Tidak ada cleanup agresif yang berisiko.

## Verification

Static implementation review selesai. Runtime `php artisan test` / `npm run build` belum dapat dieksekusi melalui connector pada sesi ini, sehingga tidak diklaim green tanpa runner.

**Implementation status: COMPLETE.**
