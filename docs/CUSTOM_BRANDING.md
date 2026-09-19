# CUSTOM_BRANDING.md

Branding LMS Sekolah dikelola dari dashboard admin tanpa edit kode.

## Membuka Pengaturan Sekolah

1. Login sebagai admin.
2. Buka menu `Pengaturan Sekolah`.
3. Ubah data yang dibutuhkan.
4. Klik `Simpan`.

Cache pengaturan sekolah otomatis dibersihkan setelah data disimpan.

## Mengganti Nama Sekolah

Isi field:

- `Nama Sekolah`
- `Nama Pendek Sekolah`
- `Motto`

Nama sekolah akan tampil di login, layout aplikasi, title halaman, footer, dan kop laporan.

## Mengganti Logo

Upload file pada field `Logo`.

Format yang didukung:

- `jpg`
- `jpeg`
- `png`
- `webp`

Ukuran maksimal: 2 MB.

Logo digunakan pada login, navbar/sidebar, welcome page, dan kop laporan.

## Mengganti Favicon

Upload file pada field `Favicon`.

Format yang didukung:

- `ico`
- `png`
- `jpg`
- `jpeg`
- `webp`

Ukuran maksimal: 1 MB.

Favicon digunakan oleh browser tab dan layout aplikasi.

## Mengganti Warna Tema

Atur field:

- `Primary Color`
- `Secondary Color`
- `Sidebar Color`
- `Navbar Color`

Gunakan format hex, contoh:

```text
#198754
#0d6efd
```

Warna tema diterapkan pada layout utama dan elemen visual aplikasi.

## Mengganti Kepala Sekolah

Isi field:

- `Nama Kepala Sekolah`
- `NIP Kepala Sekolah`
- `NUPTK Kepala Sekolah`

Data ini dipakai pada laporan, export, dan print yang membutuhkan tanda tangan atau identitas kepala sekolah.

## Mengganti Tahun Ajaran dan Semester

Ada dua tempat terkait tahun ajaran:

1. `Pengaturan Sekolah`: mengatur teks tahun ajaran dan semester yang muncul pada kop/identitas laporan.
2. `Tahun Ajaran`: mengatur tahun ajaran aktif untuk data akademik aplikasi.

Pastikan keduanya sinkron sebelum mencetak laporan.

## Memastikan Laporan Ikut Berubah

Setelah mengubah branding:

1. Refresh halaman dashboard.
2. Buka rekap admin untuk absensi, nilai, tugas, atau sikap.
3. Coba print dari browser.
4. Export PDF.
5. Export Excel.

Kop laporan harus menampilkan logo, nama sekolah, alamat, tahun ajaran, semester, dan kepala sekolah terbaru.

## Troubleshooting

- Jika logo belum berubah, pastikan file berhasil terupload dan `php artisan storage:link` sudah dijalankan.
- Jika data lama masih tampil, jalankan `php artisan optimize:clear`.
- Jika favicon belum berubah di browser, lakukan hard refresh atau hapus cache browser.
