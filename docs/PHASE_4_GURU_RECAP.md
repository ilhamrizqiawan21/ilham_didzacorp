# PHASE 4 — REKAP GURU

Branch: `experimental/demo-lms`

## Scope

Migrasi dua menu Guru yang masih menjadi outlier Blade setelah Phase 3:

- `/guru/rekap-nilai`
- `/guru/rekap-sikap`

## Implementasi

- Rekap Nilai sekarang merender `Guru/Rekap/Nilai` melalui Inertia.
- Rekap Sikap sekarang merender `Guru/Rekap/Sikap` melalui Inertia.
- Filter kelas/mapel dan semester dipertahankan.
- Pagination Rekap Nilai dipertahankan.
- Data bisnis, query kepemilikan guru, dan filter semester tetap dibatasi pada kelas-mapel yang diampu guru.
- UI dibuat responsive dengan horizontal table scrolling untuk dataset lebar.
- State kosong dan jumlah data ditampilkan secara eksplisit.
- Regression test ditambahkan untuk kedua endpoint.

## Compatibility

Route URL dan route name lama dipertahankan. Migrasi dilakukan melalui controller binding sehingga workflow input/store/export yang sudah stabil tidak perlu diubah atau diduplikasi.

Blade `resources/views/guru/rekap-nilai.blade.php` dan `resources/views/guru/rekap-sikap.blade.php` sengaja belum dihapus pada phase ini sebagai fallback artefak historis. Setelah runtime verification Phase 4 dinyatakan green, keduanya dapat dihapus pada cleanup phase berikutnya jika pencarian referensi memastikan sudah tidak digunakan.

## Acceptance

- [x] Rekap Nilai memakai Inertia.
- [x] Rekap Sikap memakai Inertia.
- [x] Filter dipertahankan.
- [x] Pagination Rekap Nilai dipertahankan.
- [x] Authorization query tetap membatasi data ke guru terkait.
- [x] Responsive table.
- [x] Empty state.
- [x] Regression test.
- [ ] Runtime `npm run build` pada branch terbaru.
- [ ] Runtime `php artisan test` pada branch terbaru.
- [ ] Manual smoke test kedua menu.

**Implementation status: COMPLETE — runtime verification required before phase sign-off.**
