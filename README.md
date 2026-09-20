# LMS Sekolah

> **Single-school Learning Management System** untuk sekolah, madrasah, dan lembaga pendidikan yang membutuhkan platform pembelajaran digital mandiri.

[![CI](https://github.com/ilhamrizqiawan21/ilham_didzacorp/actions/workflows/ci.yml/badge.svg)](https://github.com/ilhamrizqiawan21/ilham_didzacorp/actions/workflows/ci.yml)

LMS Sekolah adalah aplikasi web generik untuk satu sekolah dengan satu akun guru/pengelola dan banyak siswa. Seluruh data akademik—mata pelajaran, kelas, siswa, materi, tugas, penilaian, absensi, komunikasi, notifikasi, kalender, laporan, dan branding sekolah—dapat dikonfigurasi dari aplikasi.

**Scope produk:** single-school. Project ini belum dirancang sebagai SaaS atau multi-tenant.

---

## Highlights

- Role-based application untuk **Guru/Pengelola dan Siswa**.
- Manajemen akademik: tahun ajaran, semester, kelas, siswa, mata pelajaran, guru pengampu, dan kelas-mapel.
- Pembelajaran: materi, tugas, submission, multi-file submission, penilaian, dan catatan guru.
- Akademik: absensi, nilai akademik, sikap spiritual, sikap sosial, rekap, dan laporan.
- Komunikasi: chat kelas, notifikasi, pengumuman, dan kalender akademik.
- Import siswa dari Excel dengan template.
- Export laporan ke Excel dan PDF dengan identitas sekolah dinamis.
- Branding sekolah dari dashboard: nama, logo, favicon, kontak, kepala sekolah, warna tema, visi, misi, dan data legal.
- Security foundation: policy/authorization, security middleware, rate limiting, sensitive endpoint guard, security headers, dan audit logging.
- Automated test suite dan CI untuk validasi backend serta frontend build.
- Dokumentasi instalasi, arsitektur, audit, security, testing, branding, dan kesiapan komersial.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3+, Laravel 13 |
| Frontend | Vue 3, Inertia.js, Blade |
| UI | Bootstrap 5, Bootstrap Icons |
| Build | Vite, Node.js |
| Database | MySQL 8 / MariaDB 10.6+ |
| Charts | Chart.js |
| PDF | DomPDF |
| Spreadsheet | OpenSpout |
| Testing | PHPUnit / Laravel Test Suite |
| CI | GitHub Actions |

## Architecture

```text
Browser
   │
   ├── Vue 3 + Inertia
   │
   ▼
Laravel Application
   ├── Controllers       HTTP orchestration
   ├── Policies          Authorization
   ├── Services          Business logic
   ├── Models            Domain/data access
   ├── Middleware        Security & request controls
   └── Helpers           Shared application utilities
   │
   ▼
MySQL / MariaDB
```

Business logic yang dapat digunakan lintas controller ditempatkan pada service layer. Authorization ditangani melalui policy dan middleware, sedangkan migration menjadi sumber perubahan schema yang versioned.

## User Roles

| Role | Fokus |
|---|---|
| **Guru/Pengelola** | User, kelas, siswa, mapel, penugasan kelas, pengaturan sekolah, sistem, rekap, export, absensi, materi, tugas, nilai, sikap, chat, dan notifikasi |
| **Siswa** | Materi, pengumpulan tugas, nilai/progress, chat, kalender, dan notifikasi |

## Project Structure

```text
app/
├── Helpers/
├── Http/
│   ├── Controllers/
│   └── Middleware/
├── Models/
├── Policies/
├── Providers/
└── Services/

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── css/
├── js/
│   ├── Components/
│   ├── Layouts/
│   └── Pages/
└── views/

tests/
├── Feature/
└── Unit/

docs/
└── project, architecture, security, installation, testing, and product documentation
```

## Quick Start

### Requirements

- PHP 8.3+
- Composer 2+
- Node.js 20+
- MySQL 8.0+ atau MariaDB 10.6+

### Installation

```bash
git clone https://github.com/ilhamrizqiawan21/ilham_didzacorp.git
cd ilham_didzacorp

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Configure database pada `.env`, lalu jalankan:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
php artisan serve
```

Untuk development dengan Vite:

```bash
npm run dev
```

> Untuk production, gunakan environment terpisah, `APP_DEBUG=false`, credential database dengan privilege minimal, dan deployment asset hasil `npm run build`.

Panduan lengkap: [`docs/INSTALLATION.md`](docs/INSTALLATION.md).

## Demo Account

Seeder demo menyediakan akun demo standar untuk pengujian. Jangan gunakan akun demo pada
production. Untuk instalasi produk kosong, gunakan `EmptyProductSeeder` dan konfigurasi
`DEFAULT_ADMIN_PASSWORD` yang kuat melalui environment.

| Role | Email | Password |
|---|---|---|
| Guru | `guru@demo.test` | `password` |
| Siswa | `siswa@demo.test` | `password` |

## Testing & CI

Jalankan test suite secara lokal:

```bash
composer test
```

Format/lint PHP untuk area hardening:

```bash
composer format
composer lint
```

Build frontend:

```bash
npm run build
```

CI menjalankan Composer validate, lint PHP, test suite Laravel, dan frontend build melalui GitHub Actions.

## Security

Project memiliki fondasi security yang mencakup authorization policy, security headers, rate limiting, sensitive endpoint protection, password-change enforcement, dan academic audit logging.

Security-related documentation tersedia di:

- [`docs/PHASE-10-SECURITY.md`](docs/PHASE-10-SECURITY.md)
- [`docs/SECURITY_CHECK_RESULT.md`](docs/SECURITY_CHECK_RESULT.md)

## Documentation

### Setup & Development

- [`docs/INSTALLATION.md`](docs/INSTALLATION.md) — instalasi dan deployment
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — struktur dan pola arsitektur
- [`docs/IMPORT_SISWA.md`](docs/IMPORT_SISWA.md) — import siswa melalui Excel

### Engineering & Quality

- [`docs/CODE_AUDIT.md`](docs/CODE_AUDIT.md) — audit codebase
- [`docs/PHASE_0_AUDIT.md`](docs/PHASE_0_AUDIT.md) — baseline audit
- [`docs/PHASE_1_CORE_STABILITY.md`](docs/PHASE_1_CORE_STABILITY.md) — stabilitas core
- [`docs/PHASE-10-SECURITY.md`](docs/PHASE-10-SECURITY.md) — security hardening
- [`docs/SECURITY_CHECK_RESULT.md`](docs/SECURITY_CHECK_RESULT.md) — security verification
- [`docs/MANUAL_TEST_RESULT.md`](docs/MANUAL_TEST_RESULT.md) — hasil pengujian manual
- [`docs/FRONTEND_CONTRAST_CHECKLIST.md`](docs/FRONTEND_CONTRAST_CHECKLIST.md) — checklist contrast/accessibility

### Product

- [`docs/CUSTOM_BRANDING.md`](docs/CUSTOM_BRANDING.md) — konfigurasi identitas sekolah
- [`docs/COMMERCIAL_READY_CHECKLIST.md`](docs/COMMERCIAL_READY_CHECKLIST.md) — kesiapan produk
- [`docs/FRONTEND_TODO.md`](docs/FRONTEND_TODO.md) — pekerjaan frontend yang tersisa
- [`docs/LMS_MODERN_UI_TODO.md`](docs/LMS_MODERN_UI_TODO.md) — roadmap polish UI

## Engineering Notes

Project ini dikembangkan sebagai aplikasi production-oriented. Fokus utamanya adalah menjaga **stabilitas fungsi, backward compatibility, data integrity, security, dan maintainability** sebelum menambahkan fitur baru.

Beberapa keputusan engineering penting:

- Migration digunakan untuk perubahan schema yang terkontrol dan versioned.
- Business logic yang reusable dipisahkan ke service layer.
- Authorization tidak hanya bergantung pada visibility UI.
- Export dan import dipisahkan dari controller melalui service khusus.
- Environment secrets tidak disimpan di repository.
- Dependency lock files dipertahankan untuk reproducible installation.

## Scope & Roadmap

Saat ini project berfokus pada single-school deployment. Pengembangan berikutnya diprioritaskan pada:

1. UI/UX consistency dan responsive polish.
2. Dark/light theme system.
3. Test coverage dan regression prevention.
4. Performance dan production hardening.
5. Dokumentasi deployment dan operasional yang lebih lengkap.

Multi-tenant SaaS, billing, dan subscription management **belum menjadi scope project saat ini**.

## License

MIT. Lihat [`LICENSE`](LICENSE).

## Developer

**Ilham Rizqiawan**

Repository ini juga berfungsi sebagai portfolio engineering project: menunjukkan pengembangan aplikasi domain-specific dari feature implementation, database evolution, security hardening, testing, hingga deployment.
