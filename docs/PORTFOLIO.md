# Portfolio Overview

Dokumen ini membantu reviewer teknis memahami project dari sudut pandang engineering, bukan hanya daftar fitur.

## Problem

Sekolah membutuhkan satu sistem untuk mengelola aktivitas pembelajaran dan data akademik yang sebelumnya tersebar di berbagai proses manual atau aplikasi terpisah.

## Engineering Scope

Project mencakup lebih dari CRUD dasar. Area engineering yang dikerjakan meliputi:

- Role-based access dan authorization.
- Relational academic domain dengan foreign key dan constraint.
- Service layer untuk business logic yang reusable.
- Versioned database migrations.
- Import/export spreadsheet dan laporan PDF.
- Upload validation dan file handling.
- Notification, chat, calendar, dan academic workflow.
- Security middleware, rate limiting, security headers, sensitive endpoint protection, dan audit logging.
- Automated tests dan CI.
- Production deployment dan environment configuration.
- Frontend component/layout system berbasis Vue 3 + Inertia.

## What This Project Demonstrates

### Backend

Kemampuan merancang dan memelihara aplikasi Laravel dengan controller, model, policy, middleware, service, migration, validation, dan test feature.

### Frontend

Kemampuan membangun halaman role-specific dengan Vue 3 + Inertia, reusable components, form handling, state/feedback UI, responsive layout, dan integrasi dengan backend.

### Database

Kemampuan melakukan schema evolution melalui migration serta menjaga integritas relasi dan constraint pada domain akademik.

### Security

Security diperlakukan sebagai bagian dari architecture, bukan hanya fitur login. Authorization, request protection, security headers, rate limiting, dan auditability menjadi bagian dari aplikasi.

### Delivery

Project memiliki installation documentation, environment template, dependency lock files, automated CI, test suite, dan production-oriented configuration.

## Current Scope

Project saat ini merupakan **single-school LMS**. Multi-tenant SaaS, billing, subscription management, dan tenant isolation belum menjadi bagian dari scope.

## Reviewer Starting Points

Jika hanya memiliki beberapa menit untuk review repository, urutan yang disarankan:

1. `README.md` — gambaran produk dan arsitektur.
2. `docs/ARCHITECTURE.md` — struktur aplikasi.
3. `app/Services/` — business logic dan reusable application services.
4. `app/Policies/` dan `app/Http/Middleware/` — authorization dan security.
5. `database/migrations/` — evolusi schema.
6. `tests/Feature/` — contoh behavior-level testing.
7. `.github/workflows/ci.yml` — automated validation.

## Roadmap

Prioritas pengembangan berikutnya adalah UI/UX consistency, responsive polish, dark/light theme system, test coverage, performance, dan production hardening. Fitur baru tidak diprioritaskan jika mengorbankan stabilitas core.
