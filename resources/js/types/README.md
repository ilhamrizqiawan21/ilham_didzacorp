# Tipe bersama frontend

Lokasi tipe lintas halaman untuk migrasi TypeScript bertahap. Tipe domain
auth, school, navigation, notifications, pagination, dan shared props Inertia
ditambahkan pada Tahap 1 berdasarkan payload backend sebenarnya.

Gunakan `import type` untuk tipe. Jangan menambahkan kode runtime atau
deklarasi `any` global untuk menyembunyikan error migrasi.

Kontrak penilaian tersedia di `assessment.ts`, laporan di `reports.ts`,
pengumuman di `announcements.ts`, dan item UI bersama di `ui.ts`.
Props khusus satu halaman tetap didefinisikan di halaman pemiliknya.

`npm run typecheck` memeriksa cakupan script TypeScript seluruh SFC terlebih
dahulu, lalu menjalankan pemeriksaan strict termasuk browser test.
