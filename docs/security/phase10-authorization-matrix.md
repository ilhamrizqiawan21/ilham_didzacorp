# Phase 10 — Authorization Matrix & IDOR Audit

Branch: `experimental/demo-lms`

## Audit layers

1. **Role boundary** — `auth` + `role:*` middleware.
2. **Resource authorization** — `can:*` middleware/policies or explicit controller ownership checks.
3. **Nested-resource integrity** — child records must belong to the URL-supplied parent.

## Controller-by-controller matrix

| Controller / area | Role boundary | Resource authorization | Nested ownership | IDOR |
|---|---|---|---|---|
| Admin\UserController | `role:admin` | Admin-only; staff targets rejected when they are students | N/A | PASS |
| Admin\KelasController | `role:admin` | Admin-only | N/A | PASS |
| Admin\MataPelajaranController | `role:admin` | Admin-only | N/A | PASS |
| Admin\KelasMapelController | `role:admin` | Admin-only | Relationship validation | PASS |
| Admin\KelasSiswaController | `role:admin` | Admin-only | Student/class validation | PASS |
| Admin\TahunAjaranController | `role:admin` | Admin-only | N/A | PASS |
| Admin\PengumumanController | role-specific groups | Role/creator/class checks | Class-subject ownership for teacher operations | PASS |
| Admin\KalenderController | `role:admin` | Admin-only | Event scope/ownership | PASS |
| Admin\RekapController | `role:admin` | Admin-only | Query-scoped reports | PASS |
| Admin\SchoolSettingController | `role:admin` | Admin-only | N/A | PASS |
| Admin\SystemController | `role:admin` | Admin-only | Blocked-IP records admin-only | PASS |
| Admin\AcademicAuditLogController | `role:admin` | Admin-only | N/A | PASS |
| NotificationController | role-specific groups | Notification owner must equal `Auth::id()` | N/A | PASS |
| Guru\KelasMapelWorkspaceController | `role:guru` | `can:mengajar,kelasMapel` | N/A | PASS |
| Guru\AbsensiController | `role:guru` | `can:mengajar,kelasMapel` | Attendance scoped to authorized class-subject | PASS |
| Guru\MateriController | `role:guru` | `can:mengajar,kelasMapel` | Material parent relationship validated | PASS |
| Guru\TugasController | `role:guru` | `can:mengajar,kelasMapel`; delete also uses `can:mengajar-tugas,tugas` | Task/submission/student/file parents validated | PASS |
| Guru\NilaiController | `role:guru` | `can:mengajar,kelasMapel` | Student/class relationship checked | PASS |
| Guru\SikapController | `role:guru` | `can:mengajar,kelasMapel` | Student/class relationship checked | PASS |
| Guru\WaliKelasController | `role:guru` | `can:kelola-wali-kelas,waliKelas` | Child records bound to `wali_kelas_id`; students bound to class | PASS |
| Guru\ChatController | `role:guru` | `can:mengajar,kelasMapel` | Chat room bound to authorized class-subject | PASS |
| Guru\NotifikasiController | `role:guru` | Notification ownership check | N/A | PASS |
| Siswa\KelasMapelWorkspaceController | `role:siswa` | Student class/active checks | N/A | PASS |
| Siswa\MateriController | `role:siswa` | Active class-subject must belong to student's class | Material bound to supplied class-subject | PASS |
| Siswa\TugasController | `role:siswa` | Active task must belong to student's class | Submission/file bound to task + current student | PASS |
| Siswa\ChatController | `role:siswa` | Active class-subject must belong to student's class | Chat scoped by class-subject | PASS |
| Siswa\NotifikasiController | `role:siswa` | Notification ownership check | N/A | PASS |
| Siswa\PengumumanController | `role:siswa` | Visibility/target filtering | N/A | PASS |
| Kepsek\LaporanController | `role:kepala_sekolah` | `can:lihat-laporan-wali-kelas,waliKelas` | Active Wali Kelas scope | PASS |
| Kepsek\KalenderController | `role:kepala_sekolah` | Monitoring-only; mutations return 403 | N/A | PASS |
| Kepsek\StatistikController | `role:kepala_sekolah` | Kepsek-only | Query-scoped reports | PASS |

## Parameterized route review

### Guru

- `{kelasMapel}` routes for workspace, attendance, materials, tasks, grades, attitude and chat use `can:mengajar,kelasMapel`.
- `{tugas}` deletion uses `can:mengajar-tugas,tugas` and the controller retains its own authorization check.
- `{waliKelas}` routes use `can:kelola-wali-kelas,waliKelas`.
- `{tugas}`, `{pengumpulan}`, `{file}`, `{siswa}`, `{pertemuan}` and `{penanganan}` are checked against their parent relationship in controllers.

### Siswa

- `{kelasMapel}` is checked against the authenticated student's `kelas_id` and active state.
- `{tugas}` is checked against the student's class and active class-subject.
- `{file}` and `{pengumpulan}` are checked against the supplied task and the authenticated student's own submission.
- `{pengumuman}` and `{notifikasi}` are scoped by visibility/ownership.

### Kepala Sekolah

- `{waliKelas}` report detail is protected by `lihat-laporan-wali-kelas`.
- Calendar mutation routes deliberately return 403 for the monitoring-only role.
- Announcement and notification targets are role/owner scoped.

### Admin

- Parameterized administrative resources remain inside `role:admin`.
- Student/staff target validation prevents crossing resource domains.
- Legacy password export/reset endpoints are intercepted by `SensitiveEndpointGuard` before their unsafe legacy controller actions can execute.

## Phase 10 remediation

### Task deletion

Added `TugasPolicy::mengajar()` and registered the `mengajar-tugas` Gate. The route now performs resource authorization before entering the controller, while the controller keeps its existing authorization as defense-in-depth.

### Sensitive password endpoints

`SensitiveEndpointGuard` intercepts legacy staff/student password reset and password export endpoints. Per current operational policy, reset returns accounts to the shared default credential (`User::DEFAULT_PASSWORD`), stores only its hash, marks the account as requiring a password change, and exposes the credential only to the initiating authenticated admin. Export contains only identity and password-status metadata and must not include plaintext default credentials.

## Automated runtime coverage added

`tests/Feature/Phase10AuthorizationTest.php` covers:

- Guru A attempting to delete Guru B's task → **403** and record remains.
- Task owner deleting their own task → **allowed**.
- Admin student reset → reset to `User::DEFAULT_PASSWORD`, hashed storage and default-password flag.
- Student attempting to access the admin reset endpoint → **403**.

Existing Wali Kelas feature coverage additionally verifies cross-guru Wali Kelas isolation and student/class boundaries.

## Verification status

**Source audit:** complete.

**Automated test coverage:** added to the branch CI suite.

**CI execution:** must be confirmed by GitHub Actions/deployment infrastructure. This environment cannot execute the project's PHP dependency stack locally.

## Conclusion

No unmitigated high-risk IDOR was identified in the audited parameterized route set. The highest-risk teacher task mutation and sensitive password endpoints now have explicit route/middleware defenses in addition to controller-level checks.
