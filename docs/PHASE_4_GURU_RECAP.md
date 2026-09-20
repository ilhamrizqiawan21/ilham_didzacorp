# PHASE 4 — REKAP GURU

Branch: `experimental/demo-lms`

## Scope

Migrasi dua menu Guru yang masih menjadi outlier Blade setelah Phase 3:

- `/guru/rekap-nilai`
- `/guru/rekap-sikap`

## Implementasi

- Rekap Nilai merender `Guru/Rekap/Nilai` melalui Inertia.
- Rekap Sikap merender `Guru/Rekap/Sikap` melalui Inertia.
- Filter kelas/mapel dan semester dipertahankan.
- Pagination Rekap Nilai dipertahankan.
- Data bisnis, query kepemilikan guru, dan filter semester tetap dibatasi pada kelas-mapel yang diampu guru.
- UI dibuat responsive dengan horizontal table scrolling untuk dataset lebar.
- State kosong dan jumlah data ditampilkan secara eksplisit.
- Regression test ditambahkan untuk kedua endpoint.

## Compatibility

Route URL dan route name lama dipertahankan. Route kini menunjuk langsung ke controller rekap Inertia khusus; binding sementara dan template Blade lama sudah dihapus setelah tidak ditemukan referensi runtime.

## Acceptance

- [x] Rekap Nilai memakai Inertia.
- [x] Rekap Sikap memakai Inertia.
- [x] Filter dipertahankan.
- [x] Pagination Rekap Nilai dipertahankan.
- [x] Authorization query tetap membatasi data ke guru terkait.
- [x] Responsive table.
- [x] Empty state.
- [x] Regression test.
- [x] Runtime `npm run build` pada branch terbaru.
- [x] Runtime `php artisan test` pada branch terbaru.
- [ ] Manual smoke test kedua menu.

**Implementation status: COMPLETE — runtime verification required before phase sign-off.**
