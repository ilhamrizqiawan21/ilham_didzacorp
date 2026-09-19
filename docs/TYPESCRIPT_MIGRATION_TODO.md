# TODO Migrasi TypeScript Bertahap

Dokumen ini menjadi rencana migrasi TypeScript untuk frontend Inertia + Vue.
Migrasi dilakukan per lapisan agar halaman Blade lama, route hybrid, dan alur
form Laravel tetap berjalan selama proses berlangsung.

## Kondisi saat ini

- [x] Vue 3 dan `@inertiajs/vue3` sudah dipakai untuk halaman Inertia.
- [x] Vite sudah memakai plugin Vue.
- [x] `tsconfig.json` tersedia untuk migrasi bertahap.
- [x] Entrypoint, utilitas, dan barrel frontend memakai `.ts`.
- [x] Seluruh 115 komponen Vue memakai `<script setup lang="ts">`.
- [x] Kontrak props Inertia dan tipe domain tersedia di `resources/js/types/`.
- [x] Navigasi hybrid Blade/Inertia dipertahankan sesuai kontrak menu.

## Prinsip yang wajib dijaga

- Migrasikan komponen kecil lebih dulu, lalu naik ke app shell dan halaman.
- Jangan mengubah route, payload controller, atau perilaku form hanya karena
  perubahan ekstensi file.
- Pertahankan Bootstrap, token tema, dan komponen UI yang sudah dipakai.
- TypeScript dipakai untuk menangkap kesalahan saat build; runtime tetap Vue.
- Hindari `any` sebagai solusi permanen. Jika bentuk data belum diketahui,
  gunakan `unknown` lalu validasi atau sempitkan tipenya.
- Setiap fase harus bisa dibuild dan dirilis tanpa menunggu migrasi total.

## Tahap 0 — Fondasi dan baseline

- [x] Tambahkan `typescript`, `vue-tsc`, dan `@types/node` sebagai dev dependency.
- [x] Tambahkan `tsconfig.json` dengan `allowJs: true`, `noEmit: true`, dan
      `strict: false` pada fase awal.
- [x] Tambahkan script `typecheck: vue-tsc --noEmit`.
- [x] Pastikan `npm run build` dan `npm run typecheck` berjalan di CI/dev.
- [x] Catat error awal typecheck sebagai baseline, bukan langsung memperbaiki
      seluruh project sekaligus.
- [x] Tetapkan `resources/js/types/` sebagai lokasi tipe lintas halaman.

Definition of done:

- Toolchain TypeScript terpasang tanpa memindahkan file produksi.
- Build Vite tetap berhasil.
- Typecheck boleh memiliki daftar error awal yang terdokumentasi, tetapi tidak
  menambah error baru tanpa alasan.

### Hasil Tahap 0 (12 September 2026)

- Terpasang: TypeScript 5.9.3, vue-tsc 3.3.11, dan @types/node 20.19.43.
- TypeScript dibatasi ke seri 5.9 dan vue-tsc ke seri 3.3 untuk menjaga
  toolchain yang sudah diverifikasi; tipe Node mengikuti Node 20 di CI/dev.
- Baseline `npm run typecheck`: 0 diagnostic dengan konfigurasi tahap awal.
- `allowJs: true`, `checkJs: false`, `strict: false`, dan `noEmit: true`.
  Hasil bersih ini tidak berarti semua JavaScript lama sudah memiliki tipe.
- `skipLibCheck: true` melewati pemeriksaan internal declaration dependency.
- File JS, TS, TSX, Vue, dan konfigurasi Vite masuk cakupan konfigurasi.
- Tipe Vite client dan Node tersedia; lokasi tipe bersama mempunyai README
  di `resources/js/types/`. Tipe domain tetap pekerjaan Tahap 1.
- CI menjalankan typecheck setelah `npm ci`, tanpa `continue-on-error`.
  Build frontend tetap menjadi langkah CI yang sudah tersedia.
- Perintah lokal: `npm ci`, `npm run typecheck`, kemudian `npm run build`.
- Tidak ada file produksi yang diganti ekstensi pada tahap ini.

## Tahap 1 — Tipe domain bersama

- [x] Buat `resources/js/types/auth.ts` untuk `User`, `Role`, dan role label.
- [x] Buat `resources/js/types/school.ts` untuk branding sekolah dan tema.
- [x] Buat `resources/js/types/navigation.ts` untuk `SidebarMenuEntry` dan
      capability flags.
- [x] Buat `resources/js/types/notifications.ts` untuk unread count dan item.
- [x] Buat `resources/js/types/pagination.ts` untuk paginator Laravel/Inertia.
- [x] Buat `resources/js/types/inertia.ts` untuk `PageProps` global minimal.
- [x] Gunakan tipe yang sama pada `AppShell`, `Topbar`, `Sidebar`, dan page.

### Hasil Tahap 1 (12 September 2026)

- Tipe domain dibuat berdasarkan shared props aktual dari
  `HandleInertiaRequests`: auth, school, theme, capabilities, notifications,
  flash, dan paginator.
- `resources/js/types/index.ts` menjadi barrel export untuk import type yang
  konsisten.
- Komponen Vue belum diubah ke TypeScript; penerapan tipe pada komponen menjadi
  pekerjaan Tahap 2 dan Tahap 3 agar perubahan tetap kecil dan mudah diverifikasi.
- `npm run typecheck` berhasil tanpa diagnostic.

Prioritas tipe:

1. Data shared props yang dipakai semua halaman.
2. Menu sidebar dan link hybrid Inertia/Blade.
3. Bentuk paginator dan flash message.
4. Data tabel/dashboard yang sudah stabil.

## Tahap 2 — Komponen UI berisiko rendah

- [x] Migrasikan `Badge.vue`.
- [x] Migrasikan `IconButton.vue`.
- [x] Migrasikan `Button.vue`.
- [x] Migrasikan `EmptyState.vue`.
- [x] Migrasikan `InputError.vue`.
- [x] Migrasikan `Pagination.vue`.
- [x] Migrasikan `TableWrapper.vue`.
- [x] Migrasikan `Card.vue` dan `StatCard.vue`.
- [x] Migrasikan komponen form dasar setelah bentuk `modelValue` jelas.

### Hasil Tahap 2 (12 September 2026)

- Komponen UI dasar dan form `TextInput`, `TextareaInput`, `SelectInput`, serta
  `FileInput` sekarang memakai `<script setup lang="ts">`.
- Props dan emits sudah memakai tipe statis, termasuk tipe method Inertia,
  pagination link, opsi select, pesan validasi, dan nilai file.
- Markup, nama props, event `v-model`, class CSS, dan perilaku runtime tetap
  dipertahankan.
- `npm run typecheck` berhasil tanpa diagnostic.
- Komponen app shell dan halaman belum dipindahkan; itu masuk Tahap 3 dan 4.

Definition of done per komponen:

- Props, emits, dan slot sudah bertipe.
- Tidak ada perubahan markup atau class CSS yang tidak diperlukan.
- Showcase `InertiaTest.vue` tetap tampil.
- `npm run typecheck` dan `npm run build` lulus untuk perubahan tersebut.

## Tahap 3 — App shell dan navigasi

- [x] Migrasikan `sidebarMenu.js` menjadi `sidebarMenu.ts`.
- [x] Migrasikan `SidebarLink.vue`.
- [x] Migrasikan `Topbar.vue`.
- [x] Migrasikan `Sidebar.vue`.
- [x] Migrasikan `AppShell.vue` terakhir pada tahap ini.
- [x] Ketik event `toggle-sidebar`, `open-command`, dan `update:open`.
- [x] Ketik state viewport, menu, notifications, dan capabilities.
- [x] Tambahkan skenario drawer desktop, tablet, dan mobile dalam Playwright.
- [x] Pastikan route Blade tetap memakai navigasi native dan route Inertia tetap
      memakai `<Link>`.

### Hasil Tahap 3 (12 September 2026)

- App shell, sidebar, topbar, sidebar link, dan menu role sekarang memakai
  TypeScript.
- Shared props memakai `AppPageProps`, termasuk auth, school, notifications,
  capabilities, dan tema.
- State menu, breakpoint viewport, command shortcut, dan event emit sudah
  memiliki tipe.
- Strategi hybrid tetap dipertahankan: route Blade memakai anchor native dan
  route Inertia memakai `Link`.
- Test manual drawer desktop/tablet/mobile tetap menjadi pemeriksaan manual
  karena belum ada browser test dalam repository.
- `npm run typecheck` berhasil tanpa diagnostic.

## Tahap 4 — Halaman read-only

Urutan aman berdasarkan risiko:

- [x] Admin Dashboard.
- [x] Guru Dashboard.
- [x] Siswa Dashboard.
- [x] Kepala Sekolah Dashboard.
- [x] Halaman statistik dan laporan yang hanya membaca data.

Untuk setiap halaman:

- [x] Tipe props controller/Inertia ditulis eksplisit untuk dashboard dan statistik.
- [x] Tipe item tabel dan kartu dashboard tidak memakai `any`.
- [x] Nilai nullable dari Laravel ditangani dengan aman pada halaman yang dimigrasikan.
- [x] Chart refs dan lazy import Chart.js dashboard/statistik diberi tipe.
- [x] Pemeriksaan visual hasil migrasi melalui screenshot lintas viewport.
      Screenshot sebelum migrasi tidak tersedia; ini bukan klaim pixel-diff
      sebelum/sesudah. Template dan CSS diperiksa melalui diff.

### Hasil Tahap 4 (12 September 2026)

- Dashboard Admin, Guru, Siswa, dan Kepala Sekolah sudah memakai TypeScript.
- Statistik Kepala Sekolah juga sudah memakai tipe untuk data kelas, nilai,
  absensi, pengumpulan, dan instance chart.
- Chart canvas, chart lifecycle, props data, item tabel, metric, dan helper
  persentase sudah memiliki kontrak tipe.
- Tidak ada perubahan route, payload controller, markup, atau class CSS.
- Laporan read-only lain dan pemeriksaan visual lintas role tetap menjadi bagian
  lanjutan Tahap 4.
- `npm run typecheck` berhasil tanpa diagnostic.

## Tahap 5 — Form dan interaksi

- [x] Ketik `useForm` payload untuk profil, user, kelas, mapel, dan tahun ajaran.
- [x] Ketik error validasi Laravel dan flash message.
- [x] Ketik file upload dan progress upload.
- [x] Ketik filter tabel dan query string.
- [x] Ketik event modal, confirm dialog, toast, dan loading state.
- [x] Migrasikan absensi dan nilai setelah komponen form stabil.

Form berisiko tinggi seperti absensi, input nilai, upload tugas, dan chat hanya
dimigrasikan setelah halaman read-only dan form sederhana tidak memiliki error
typecheck baru.

### Hasil Tahap 5 (progres)

- Form profil guru, profil siswa, dan pengaturan akun sudah memakai payload
  `useForm` TypeScript, termasuk upload avatar dan aksi konfirmasi.
- Form kelas, mata pelajaran, dan tahun ajaran sudah memakai model data,
  payload, state editing, serta handler submit/hapus yang typed.
- Form user admin, filter tabel, query string, URL export, dan aksi konfirmasi
  sekarang sudah memakai model data serta handler typed.
- Toast/loading global sudah typed; migrasi form absensi dan nilai yang belum
  tercakup tetap dilanjutkan terpisah.

## Tahap 6 — Fitur kompleks

- [x] Kalender dan event payload.
- [x] Chart dan statistik lanjutan.
- [x] Chat dan polling/realtime-like state.
- [x] Upload materi dan pengumpulan tugas.
- [x] Rekap dengan filter bertingkat dan export link.

### Hasil Tahap 6

- Halaman ringkasan nilai siswa memakai model periode dan item nilai typed,
  termasuk helper tampilan nilai dan styling rata-rata.
- Kalender admin, guru, siswa, dan kepala sekolah memakai kontrak event,
  cell, timeline, scope, serta form yang sama.
- Chat guru dan siswa memakai kontrak room/message serta state scroll dan
  submit yang typed.
- Upload materi memiliki payload file dan progress upload typed; review
  pengumpulan tugas memiliki kontrak submission, file, status, filter, serta
  autosave nilai yang typed.
- Rekap tugas kepala sekolah memakai filter bertingkat, paginator, dan URL
  export typed.

## Tahap 7 — Pengetatan aturan

- [x] Ubah `strict` menjadi `true` setelah mayoritas komponen shell dan halaman
      utama sudah typed.
- [x] Aktifkan `noImplicitAny` dan `strictNullChecks` melalui mode strict.
- [x] Kurangi `allowJs` secara bertahap hingga dinonaktifkan.
- [x] Ubah file utilitas `.js` tersisa menjadi `.ts`.
- [x] Hapus pengecualian typecheck yang tidak lagi diperlukan.
- [x] Tambahkan typecheck sebagai pemeriksaan wajib sebelum merge.

### Hasil Tahap 7

- `strict`, `noImplicitAny`, dan `strictNullChecks` aktif melalui konfigurasi
  TypeScript utama; isu nullability pada shell/form serta union chart sudah
  diperbaiki.
- `allowJs` dinonaktifkan dan seluruh file `.js` di `resources/js` telah
  dikonversi menjadi `.ts`, termasuk entrypoint Vite dan barrel component.
- Typecheck berjalan di CI melalui `npm run typecheck` sebelum test dan build.

## Kandidat urutan file pertama

1. `resources/js/Components/UI/Badge.vue`
2. `resources/js/Components/UI/IconButton.vue`
3. `resources/js/Components/UI/EmptyState.vue`
4. `resources/js/Components/UI/Pagination.vue`
5. `resources/js/Components/AppShell/sidebarMenu.ts`
6. `resources/js/Components/AppShell/SidebarLink.vue`
7. `resources/js/Components/AppShell/Topbar.vue`
8. `resources/js/Components/AppShell/Sidebar.vue`
9. `resources/js/Layouts/AppShell.vue`
10. `resources/js/Pages/Admin/Dashboard.vue`

## Gate verifikasi setiap fase

- [x] `npm run typecheck`
- [x] `npm run build`
- [x] `php artisan view:cache`
- [x] Test route/feature yang terkait (80 test, 754 assertion)
- [x] `git diff --check`
- [x] Browser test: 13 lulus, mencakup 58 halaman, interaksi form, dan drawer pada lebar 1440/820/390px.

## Audit penutupan migrasi

- Audit menemukan script JavaScript dalam SFC masih lolos pemeriksaan awal
  meskipun `strict: true` dan `allowJs: false`. Seluruh 115 SFC kini memakai
  TypeScript; `scripts/check-typescript.mjs` mencegah regresi cakupan tersebut.
- Form absensi guru/wali kelas, nilai massal/per kelas, sikap, upload tugas,
  pengumuman, administrasi, dan semua laporan sudah memiliki tipe data dan event.
- Slot komponen bersama, command palette, confirm dialog, dan searchable select
  memiliki kontrak statis. Tidak ditambahkan `any` atau penonaktifan diagnostic.
- Command palette mengikuti flag `inertia` sebagaimana sidebar. Halaman notifikasi
  bersama kini mengakses `props.markAllReadUrl`; akses lampiran hasil pencarian
  pengumuman menangani data kosong.
- Assertion progress siswa mengikuti angka hasil serialisasi JSON. Angka bulat
  JSON tidak mempertahankan pembedaan integer/float PHP; nilai yang diharapkan
  tetap sama dan pemeriksaan null tetap dipertahankan.
- Browser test menemukan format penyimpanan tanggal absensi tidak sesuai kolom
  DATE pada SQLite, sehingga update mencoba membuat record duplikat. Model kini
  memakai format `Y-m-d`, dilindungi test regresi dan pengujian simpan/reload.
- Browser test memakai database SQLite temporer dan akun fixture tersendiri.
  Jalankan `npm run build`, `npx playwright install chromium`, lalu
  `npm run test:browser`. Runner menyalakan server lokal pada port kosong dan
  membersihkan database/server setelah selesai. Screenshot ada di `test-results/`.
- Konteks browser lokal memakai `bypassCSP` karena server PHP fixture memakai
  HTTP sedangkan CSP produksi meng-upgrade redirect ke HTTPS. Pengujian ini
  tidak menggantikan test keamanan header/CSP Laravel.
- Catatan hasil fase sebelumnya adalah riwayat pengerjaan, bukan status akhir.
- Audit lanjutan dan perbaikan runtime dicatat di
  [AUDIT_CODE_2026-09-12.md](AUDIT_CODE_2026-09-12.md). Typecheck juga menolak
  import/variabel yang tidak terpakai.

TypeScript tidak dijadikan alasan untuk mengubah API controller atau menghapus
halaman Blade. Perubahan kontrak backend dibuat dalam pekerjaan terpisah dan
harus memiliki test sendiri.
