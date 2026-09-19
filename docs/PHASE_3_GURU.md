# PHASE 3 — GURU TEACHING WORKSPACE

Branch: `experimental/demo-lms`

## Scope

Phase ini fokus pada **core teaching workflow Guru**, bukan migrasi seluruh laporan Guru. Rekap Nilai dan Rekap Sikap sengaja tetap legacy karena controller `NilaiController::rekap` dan `SikapController::rekap` masih mengembalikan Blade; migrasinya dipisahkan agar tidak mencampur perubahan UI dengan risiko perubahan query laporan.

## Audit

Frontend Guru sudah memiliki halaman Inertia untuk Dashboard, Absensi, Materi, Tugas, Nilai, Sikap, Kalender, Kelas/Mapel workspace, Chat, Wali Kelas, dan Profil. Contoh implementasi aktif:

- `Guru/Absensi/Index.vue`
- `Guru/Materi/Index.vue` dan `Guru/Materi/List.vue`
- `Guru/Tugas/Index.vue`
- `Guru/Nilai/Index.vue` dan `Guru/Nilai/Input.vue`
- `Guru/Sikap/Index.vue`
- `Guru/Kalender/Index.vue`
- `Guru/KelasMapel/Show.vue`
- `Guru/Profil.vue`

Controller yang diperiksa juga sudah menggunakan Inertia untuk workflow utama, termasuk Kalender, Materi, Nilai, Sikap, dan workspace kelas/mapel.

## Implementasi Phase 3

1. Pengumuman Guru dinormalisasi ke Inertia pada controller bersama tanpa merusak authorization Guru.
2. Kalender Guru dipastikan menggunakan `Guru/Kalender/Index.vue` dan shared `CalendarWorkspace`.
3. Sidebar Guru dinormalisasi untuk seluruh **core teaching menu** ke Inertia:
   - Dashboard
   - Absensi
   - Materi
   - Tugas
   - Nilai
   - Sikap
   - Wali Kelas bila capability tersedia
   - Kalender
   - Pengumuman
   - Chat Kelas
   - Pengaturan
4. Rekap Nilai dan Rekap Sikap tetap ditandai legacy pada sidebar sampai dedicated migration phase agar tidak terjadi false-positive frontend migration.
5. Tidak ada penghapusan Blade legacy yang belum terbukti tidak direferensikan.

## Mengapa Rekap Ditunda

Route saat ini:

- `/guru/rekap-nilai` → `NilaiController::rekap`
- `/guru/rekap-sikap` → `SikapController::rekap`

`NilaiController::rekap` masih melakukan `return view('guru.rekap-nilai', ...)`. Karena itu mengubah flag sidebar menjadi Inertia tanpa mengubah controller akan menyebabkan navigasi salah. Migrasi laporan akan menjadi phase tersendiri setelah core teaching workflow stabil.

## Cara Developer Mengecek Setiap Phase

Setelah sebuah phase selesai:

```bash
git checkout experimental/demo-lms
git pull --ff-only origin experimental/demo-lms
composer install
npm install
php artisan optimize:clear
php artisan migrate:fresh --seed
npm run build
php artisan test
php artisan serve
```

Lalu lakukan smoke test sesuai role.

### Phase 3 — akun Guru

1. Login sebagai Guru dari `DemoSeeder`.
2. Buka Dashboard.
3. Buka setiap menu core teaching.
4. Pastikan perpindahan halaman tidak full reload dan tetap berada di AppShell.
5. Absensi: pilih kelas/mapel → ubah status → simpan → reload → pastikan data tersimpan.
6. Materi: upload → buka daftar → download → hapus.
7. Tugas: buat tugas → pastikan muncul → buka pengumpulan/nilai → hapus.
8. Nilai: input nilai → simpan → reload → pastikan nilai tetap.
9. Sikap: input spiritual/social → simpan → reload.
10. Kalender: tambah/edit/hapus event pribadi.
11. Pengumuman: buat pengumuman sesuai kelas yang diampu → pastikan audience authorization benar.
12. Chat: buka workspace kelas dan kirim pesan.
13. Wali Kelas: hanya muncul jika capability `has_wali_kelas` aktif.
14. Pengaturan/Profil: ubah data profil dan uji password change.
15. Pastikan Guru tidak dapat mengakses kelas/mapel yang bukan tanggung jawabnya.

## Acceptance Criteria

- [x] Core teaching frontend Guru konsisten dengan Inertia/AppShell.
- [x] Pengumuman Guru menggunakan frontend Inertia.
- [x] Sidebar tidak mengklaim Rekap sebagai Inertia sebelum controller siap.
- [x] Authorization workflow Guru tidak dilemahkan.
- [x] Tidak ada cleanup agresif terhadap Blade yang masih direferensikan.
- [x] Batas phase terdokumentasi.

## Verification Status

Static repository audit dan implementation review selesai. Runtime test/build harus dijalankan dari checkout lokal karena connector GitHub tidak menyediakan runner shell aplikasi pada sesi ini.

**PHASE 3 CORE GURU: COMPLETE.**
**NEXT: PHASE 4 — MIGRASI & HARDENING REKAP GURU.**
