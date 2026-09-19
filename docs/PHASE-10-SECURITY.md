# PHASE 10 — Security Hardening

Status: **code-level hardening complete; production deployment gate remains runtime verification**.

## Implemented

- Role middleware protects the Admin, Guru, Siswa and Kepala Sekolah route groups.
- Object-level authorization is enforced for `KelasMapel` and `WaliKelas` through policies and route `can:` middleware.
- `TugasPolicy` now protects teacher task deletion with `can:mengajar-tugas,tugas`, while the controller keeps its own authorization check as defense-in-depth.
- Student task access verifies the authenticated student's class before viewing, submitting or downloading task data.
- Student submission/file access is bound to both the supplied task and the authenticated student's own submission.
- Teacher task/material/attendance/nilai/sikap/chat routes with `{kelasMapel}` are protected by the teacher ownership policy.
- Nested teacher resources validate their parent relationship before mutation or download.
- Uploads are stored on the private `local` disk instead of the public disk for task submissions/materials.
- Student task uploads require a safe extension plus server-detected MIME type (`image/jpeg` or `application/pdf`), with a 5 MB per-file limit and a 5-file limit.
- Download responses sanitize supplied download filenames with `basename()`.
- Laravel's `web` middleware remains enabled, preserving session, cookie and CSRF protection.
- Login attempts are rate limited to 5 attempts per minute per username/IP combination.
- A general per-route request limiter is applied to web traffic.
- Security headers include CSP, clickjacking protection, MIME sniffing protection, Referrer-Policy, Permissions-Policy and COOP; HSTS is enabled in production over HTTPS.
- Authenticated responses are marked `no-store` to reduce sensitive browser/proxy caching.
- Production can force users with default credentials to change their password before accessing the application.
- Account password changes require current-password confirmation and a stronger password policy.
- Production environment controls are documented in `.env.example`.
- Legacy admin password export is intercepted by `SensitiveEndpointGuard` and replaced with a CSV containing account identity and password-status metadata only; no password value is exported.
- Legacy admin and student password-reset routes are intercepted by `SensitiveEndpointGuard` and, per current operational policy, reset accounts to `User::DEFAULT_PASSWORD` (`123456`) with hashed storage. The credential is exposed only to the authenticated admin initiating the reset.
- Added `tests/Feature/Phase10AuthorizationTest.php` covering cross-guru task deletion, owner task deletion, secure student password reset and role-boundary rejection.

## Authorization / IDOR completion

The controller-by-controller matrix is documented in `docs/security/phase10-authorization-matrix.md`.

The source audit covers parameterized routes for Admin, Guru, Siswa and Kepala Sekolah, with explicit attention to:

- parent/child ownership;
- same-role cross-user access;
- student class boundaries;
- task/submission/file relationships;
- Wali Kelas ownership;
- notification ownership; and
- monitoring-only Kepala Sekolah routes.

No unmitigated high-risk IDOR was identified in the audited route set.

## Runtime verification gate

The repository CI workflow runs:

```text
composer install
composer validate --strict
npm ci
composer lint
php artisan test --colors=never
npm run build
```

The Phase 10 feature tests are now part of that suite. Runtime execution must be confirmed by GitHub Actions/deployment infrastructure before the application is declared production-ready.

## Production deployment checklist

- Set `APP_ENV=production` and `APP_DEBUG=false`.
- Generate a unique `APP_KEY`; never commit `.env`.
- Use HTTPS and set `SESSION_SECURE_COOKIE=true`.
- Keep `SESSION_HTTP_ONLY=true` and `SESSION_SAME_SITE=lax` unless the deployment architecture requires another value.
- Kebijakan ganti password wajib dinonaktifkan secara default. Aktifkan eksplisit
  dengan `FORCE_PASSWORD_CHANGE=true` hanya bila diperlukan oleh instalasi.
- Use a non-default database account with only the privileges required by the application.
- Ensure `storage/` and `bootstrap/cache/` are writable by the application process, while `.env` and source files are not web-writable.
- Keep private user/task files outside the public web root and serve them through authorized download controllers.
- Run `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache` after environment configuration is finalized.
- Confirm the complete CI test/build suite is green.
- Manually verify 401/403/404 behavior for every role before opening the system to school users.
