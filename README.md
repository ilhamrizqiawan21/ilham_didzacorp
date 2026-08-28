# LMS Qurdis

Learning Management System mandiri untuk pembelajaran Al-Qur'an Hadis (Qurdis). Aplikasi ini membantu guru mengelola perencanaan pembelajaran, materi, tugas, absensi, penilaian, serta komunikasi dengan siswa.

## Fitur utama

### Guru atau admin

- Dashboard dan manajemen pengguna.
- Pengelolaan kelas serta siswa.
- Capaian Pembelajaran (CP), Alur Tujuan Pembelajaran (ATP), dan bab.
- Program tahunan, program semester, jurnal, modul ajar, LKPD, dan KKTP.
- Materi Al-Qur'an dan hadis dari sumber data lokal.
- Tugas, pengumpulan, absensi, sikap, dan olah nilai.
- Export dokumen serta laporan ke Excel, HTML, dan PDF.
- Pengumuman, chat, log login, dan papan tulis digital.

### Siswa

- Mengakses materi dan tugas.
- Mengunggah serta memperbaiki pengumpulan tugas.
- Melihat nilai dan riwayat absensi.
- Mengakses materi Al-Qur'an, hadis, profil, dan chat.

## Teknologi

- PHP native
- MySQL atau MariaDB
- HTML, CSS, dan JavaScript
- Composer
- PhpSpreadsheet
- DomPDF

## Persyaratan

- PHP 7.4 atau lebih baru
- Composer
- MySQL atau MariaDB
- Apache, Nginx, XAMPP, Laragon, atau server PHP lokal

## Instalasi

```bash
git clone https://github.com/ilhamrizqiawan21/ilham_didzacorp.git
cd ilham_didzacorp
composer install
```

Buat database dan sesuaikan konfigurasi koneksi pada `config.php`. Pastikan repository atau backup SQL yang digunakan telah disanitasi dan tidak berisi data pribadi siswa.

Jalankan dengan web server lokal atau:

```bash
php -S localhost:8000
```

Buka `http://localhost:8000`.

## Struktur utama

```text
admin/       Modul administrasi dan pembelajaran guru
siswa/       Dashboard serta aktivitas siswa
ajax/        Endpoint chat dan request asinkron
assets/      Data Qur'an, hadis, gambar, dan aset frontend
includes/    Layout serta fungsi bersama
config.php   Konfigurasi aplikasi
```

## Catatan keamanan

- Jangan commit credential database atau data pribadi siswa.
- Gunakan password yang telah di-hash dan session cookie yang aman.
- Batasi ukuran serta tipe file pengumpulan tugas.
- Verifikasi otorisasi setiap halaman admin dan siswa pada sisi server.
- Aktifkan HTTPS dan nonaktifkan tampilan error pada production.

## Status proyek

Aplikasi dikembangkan untuk kebutuhan pembelajaran Qurdis. Repository ini menggunakan arsitektur PHP native; pengembangan lanjutan sebaiknya disertai automated test dan dokumentasi deployment.
