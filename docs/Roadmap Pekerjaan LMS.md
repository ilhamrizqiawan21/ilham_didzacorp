Roadmap Pekerjaan LMS
PHASE 0 — Audit & Baseline

Tujuan: mengetahui kondisi sebenarnya sebelum mengubah kode.

 Audit seluruh struktur Laravel
 Audit routes
 Audit Controller
 Audit Models & relationships
 Audit Services
 Audit Policies/authorization
 Audit migrations & database
 Audit Seeder/demo data
 Audit Vue/Inertia pages
 Audit komponen UI
 Audit fitur berdasarkan role
 Identifikasi halaman/fitur yang dummy
 Identifikasi TODO/FIXME dan unfinished implementation
 Identifikasi error potensial
 Audit responsive mobile
 Audit build frontend
 Audit testing
 Buat master backlog pekerjaan

Output: peta lengkap project + daftar pekerjaan yang belum selesai.

PHASE 1 — Stabilitas Core System

Fokus: memastikan fondasi LMS benar-benar stabil.

 Authentication
 Login/logout
 Session
 Role & permission
 Middleware
 Authorization/policy
 User management
 Error handling
 Validation
 Database relationship
 File/storage handling
 Notification system
 Security dasar

Target: semua role dapat login dan hanya bisa mengakses fitur yang memang menjadi haknya.

PHASE 2 — Admin

Fokus pada seluruh sistem administrasi LMS.

Manajemen pengguna
 Admin
 Guru
 Siswa
 Kepala sekolah
 CRUD pengguna
 Reset password
 Status aktif/nonaktif
Akademik
 Tahun ajaran
 Semester
 Kelas
 Siswa
 Mata pelajaran
 Guru pengampu
 Relasi guru ↔ kelas ↔ mapel
Branding
 Nama sekolah
 Logo
 Favicon
 Warna tema
 Informasi kepala sekolah
 Identitas sekolah

README branch ini memang sudah mendeskripsikan pengaturan branding melalui dashboard Admin.

PHASE 3 — Modul Guru

Ini salah satu phase terbesar.

Dashboard Guru
 Statistik
 Kelas yang diampu
 Tugas
 Absensi
 Aktivitas terbaru
Materi
 Buat materi
 Edit materi
 Hapus materi
 Upload file
 Download file
 Materi berdasarkan kelas/mapel
Tugas
 Buat tugas
 Edit tugas
 Deadline
 Attachment
 Pengumpulan
 Multi-file submission
 Penilaian
 Catatan guru
Absensi
 Daftar siswa
 Hadir
 Izin
 Sakit
 Alpa
 Rekap
Penilaian
 Nilai akademik
 Sikap spiritual
 Sikap sosial
 Rekap nilai
PHASE 4 — Modul Siswa

Fokus pada pengalaman belajar siswa.

 Dashboard siswa
 Daftar kelas
 Daftar mata pelajaran
 Materi
 Download materi
 Tugas
 Upload jawaban
 Multi-file submission
 Status pengumpulan
 Deadline
 Nilai
 Progress pembelajaran
 Absensi
 Kalender
 Notifikasi

Targetnya siswa bisa menggunakan LMS dari awal pembelajaran sampai melihat hasil belajarnya tanpa harus masuk ke halaman administratif.

PHASE 5 — Modul Kepala Sekolah

Fokus pada monitoring, bukan pengelolaan data.

 Dashboard kepala sekolah
 Statistik sekolah
 Statistik siswa
 Statistik guru
 Statistik kelas
 Statistik pembelajaran
 Rekap nilai
 Rekap absensi
 Laporan akademik
 Kalender
 Pengumuman
PHASE 6 — Communication System

Menyelesaikan komunikasi internal LMS.

Chat
 Chat kelas
 Pengiriman pesan
 Riwayat pesan
 Authorization chat
 UI chat
 Empty state
 Notification
Notification
 Notification guru
 Notification siswa
 Notification admin
 Notification kepala sekolah
 Mark as read
 Unread counter
Announcement
 Buat pengumuman
 Target penerima
 Edit
 Hapus
 Tampilan pengumuman
PHASE 7 — Calendar & Academic Timeline
 Kalender akademik
 Event
 Jadwal tugas
 Deadline
 Pengumuman
 Filter berdasarkan role
 Detail event
 UI kalender
PHASE 8 — Report & Export

Project sudah mempunyai PDF dan Excel sebagai bagian dari rancangan fiturnya.

Kita rapikan menjadi sistem laporan yang konsisten.

PDF
 Laporan nilai
 Laporan absensi
 Laporan tugas
 Rekap siswa
 Rekap kelas
 Kop sekolah
 Logo
 Metadata tahun ajaran
 Metadata semester
Excel
 Export siswa
 Export nilai
 Export absensi
 Export tugas
 Template import
 Validasi import

Audit sebelumnya juga mencatat ExportController masih cukup padat dan dapat dipecah menjadi service laporan terpisah.

Status Phase 8 — Export / Import

 Import siswa              ✅
 Template import           ✅
 Validasi import           ✅
 Atomic transaction        ✅
 Excel writer abstraction  ✅
 Export nilai service      ✅
 Export absensi service    ✅
 Export tugas service      ✅
 Production route switch   ✅
 Legacy ExportController   ✅
 Export siswa integration  ✅

Catatan implementasi:
 Route Excel rekap Admin untuk nilai, absensi, dan tugas sudah memakai ReportExcelExportController.
 Method Excel legacy di ExportController tetap tersedia sebagai delegasi ke service laporan.
 Export siswa Admin sudah memakai SiswaExportService dan mengikuti filter kelas, pencarian, serta status siswa.

PHASE 9 — UI/UX & Responsive

Setelah fungsi selesai, baru kita lakukan polishing besar.

Desktop
 Sidebar
 Navbar
 Dashboard
 Table
 Form
 Modal
 Dropdown
 Alert
 Pagination
Mobile
 Mobile navbar
 Sidebar mobile
 Dashboard mobile
 Table responsive
 Form responsive
 Chat mobile
 Materi mobile
 Tugas mobile
Konsistensi
 Typography
 Spacing
 Button
 Card
 Icon
 Color theme
 Empty state
 Loading state
 Error state
PHASE 10 — Security Hardening

Sebelum dianggap siap digunakan sekolah.

 Authorization seluruh route
 Policy audit
 IDOR check
 File upload security
 MIME validation
 File size validation
 Mass assignment
 CSRF
 XSS
 SQL injection review
 Rate limiting
 Sensitive information exposure
 Password/security configuration
 Production .env
 Storage permission
PHASE 11 — Testing

Ini penting karena audit sebelumnya menemukan environment testing belum lengkap.

Automated test
 Authentication test
 Authorization test
 Admin test
 Guru test
 Siswa test
 Kepala sekolah test
 Materi test
 Tugas test
 Submission test
 Nilai test
 Absensi test
 Chat test
 Notification test
 Import/export test
Manual test
 Admin workflow
 Guru workflow
 Siswa workflow
 Kepala sekolah workflow
 Desktop
 Mobile
PHASE 12 — Performance
 Query optimization
 N+1 query audit
 Eager loading
 Pagination
 Cache
 Asset optimization
 Lazy loading
 Chart optimization
 File handling
 Database indexing
 Production Laravel optimization
PHASE 13 — Production Readiness

Terakhir sebelum LMS dianggap siap dipakai sekolah.

 Fresh installation test
 EmptyProductSeeder test
 DemoSeeder test
 Migration test
 Storage link
 Production build
 Production .env
 Backup database
 Backup uploaded files
 Logging
 Error page
 Maintenance mode
 Deployment documentation
 Admin installation guide
 User guide
 Security checklist
 Commercial readiness

README sendiri sudah menyediakan jalur demo seeder dan empty product seeder, jadi dua skenario instalasi ini akan kita jadikan bagian dari validasi akhir.

Urutan kerja kita

Saya akan memperlakukan ini seperti development project sungguhan:

PHASE 0
↓
PHASE 1 — Core
↓
PHASE 2 — Admin
↓
PHASE 3 — Guru
↓
PHASE 4 — Siswa
↓
PHASE 5 — Kepala Sekolah
↓
PHASE 6 — Communication
↓
PHASE 7 — Calendar
↓
PHASE 8 — Report/Export
↓
PHASE 9 — UI/UX
↓
PHASE 10 — Security
↓
PHASE 11 — Testing
↓
PHASE 12 — Performance
↓
PHASE 13 — Production

Aturan kerja saya

Setiap phase akan memiliki status:

🔴 Belum dikerjakan → 🟡 Sedang dikerjakan → 🟢 Selesai → 🔵 Terverifikasi

Dan saya tidak akan menganggap sebuah fitur selesai hanya karena kodenya ada. Fitur harus bisa digunakan dari route → controller → database → frontend → authorization → error handling.

Untuk sekarang saya anggap PHASE 0 sebagai phase aktif. Langkah berikutnya adalah saya audit branch experimental/demo-lms secara menyeluruh dan mengubah roadmap di atas menjadi backlog konkret per file/fungsi, sehingga setelah itu kita bisa mulai mengerjakan Phase 1 secara sistematis.
