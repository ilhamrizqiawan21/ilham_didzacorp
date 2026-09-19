# PHASE 1 — CORE STABILITY

Tanggal: 2026-08-19
Branch: `experimental/demo-lms`

## Scope

Phase 1 berfokus pada authentication, authorization boundary, session handling, dan regression protection. Perubahan tidak mengubah kontrak route utama.

## Implemented

- Login tetap mendukung username atau email.
- Login rate limiting tetap aktif.
- Session ID tetap diregenerasi setelah login.
- Akun nonaktif dipastikan logout dan session di-invalidate.
- Role middleware tetap memblokir user tanpa role yang sesuai.
- Authorization exception tetap diarahkan ke dashboard role masing-masing.
- Intended URL setelah login sekarang divalidasi terhadap host dan scheme aplikasi sebelum dipakai.
- Intended URL tetap harus berada pada namespace role yang sesuai.
- Regression tests ditambahkan untuk login, inactive account, role mismatch, role match, dan external intended URL.
- GitHub Actions CI ditambahkan untuk `php artisan test` dan `npm run build` pada branch ini.

## Security Fix

Sebelumnya validasi intended URL hanya memeriksa path. URL eksternal seperti `https://attacker.example/admin/...` secara teori dapat lolos pemeriksaan role karena path-nya diawali `/admin`. Validasi sekarang menolak host eksternal dan scheme yang berbeda.

## Residu / Dead Code Policy

Tidak ada file aplikasi yang dihapus hanya berdasarkan nama atau asumsi. File hanya akan dihapus jika seluruh repository search membuktikan tidak ada referensi runtime/build/documentation yang valid. Stabilitas lebih diutamakan daripada cleanup agresif.

## Verification

Static review terhadap perubahan selesai.

CI workflow sudah ditambahkan, tetapi connector GitHub pada sesi ini tidak menyediakan eksekusi workflow/manual dispatch dan repository tidak menghasilkan workflow run yang dapat dibaca untuk commit terakhir. Karena itu hasil `php artisan test` dan `npm run build` **tidak diklaim sebagai telah dijalankan pada sesi ini**.

## Acceptance

- [x] Authentication flow ditinjau dan diperkuat.
- [x] Authorization boundary ditinjau.
- [x] Session fixation protection dipertahankan.
- [x] Open redirect pada intended URL diperbaiki.
- [x] Core regression tests ditambahkan.
- [x] CI verification pipeline ditambahkan.
- [x] Tidak ada cleanup file yang berisiko.
- [ ] Runtime CI green pada commit phase ini — menunggu runner GitHub tersedia.

**Implementation status: COMPLETE. Runtime verification status: BLOCKED BY AVAILABLE GITHUB ACTIONS EXECUTION ACCESS.**
