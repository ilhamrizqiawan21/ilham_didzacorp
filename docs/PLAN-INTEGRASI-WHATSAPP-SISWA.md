# Rencana Integrasi WhatsApp Siswa

## 1. Tujuan

Membantu guru mengirimkan pengingat WhatsApp secara manual kepada siswa yang:

- belum mengumpulkan tugas;
- terlambat mengumpulkan tugas;
- memiliki nilai yang sudah diterbitkan;
- menerima informasi tugas baru.

Versi awal tidak menggunakan WhatsApp API. LMS hanya menyiapkan pesan dan membuka chat WhatsApp melalui tautan `wa.me`.

## 2. Batasan MVP

### Termasuk

- Nomor WhatsApp siswa.
- Persetujuan menerima notifikasi.
- Template pesan personal.
- Perhitungan hari keterlambatan otomatis.
- Daftar siswa yang belum mengumpulkan tugas.
- Tombol buka WhatsApp.
- Log bahwa pengingat telah disiapkan atau dikirim manual.
- Tautan produksi menggunakan HTTPS.

### Tidak termasuk

- Pengiriman otomatis tanpa konfirmasi guru.
- Webhook status terkirim atau terbaca.
- Pengiriman massal otomatis melalui WhatsApp Web.
- Integrasi WhatsApp Business Cloud API.
- Nomor WhatsApp orang tua pada tahap pertama.

## 3. Penerima Notifikasi

Penerima utama adalah siswa. Nomor orang tua tidak diperlukan untuk MVP.

Data siswa yang diperlukan:

- `nomor_whatsapp`;
- `whatsapp_opt_in`;
- `whatsapp_opted_in_at`;
- preferensi jenis notifikasi;
- status nomor valid atau belum.

Nomor Indonesia dinormalisasi dari format `08xxxxxxxxxx` menjadi `628xxxxxxxxxx`.

## 4. Jenis Template Pesan

### Tugas belum dikumpulkan

```text
Halo {{nama_siswa}},

Tugas berikut belum kamu kumpulkan:

Mata pelajaran: {{mata_pelajaran}}
Judul tugas: {{judul_tugas}}
Batas waktu: {{batas_waktu}}
Status: Belum dikumpulkan

Silakan mengumpulkan melalui LMS:
{{url_tugas}}
```

### Tugas terlambat

```text
Halo {{nama_siswa}},

Tugas berikut belum kamu kumpulkan dan sudah melewati batas waktu:

Mata pelajaran: {{mata_pelajaran}}
Judul tugas: {{judul_tugas}}
Batas waktu: {{batas_waktu}}
Keterlambatan: {{hari_terlambat}} hari

Silakan segera mengumpulkan melalui LMS:
{{url_tugas}}
```

### Nilai tersedia

```text
Halo {{nama_siswa}},

Nilai tugas {{judul_tugas}} untuk mata pelajaran {{mata_pelajaran}} sudah tersedia.

Silakan login untuk melihat detail:
{{url_nilai}}
```

### Tugas baru

```text
Halo {{nama_siswa}},

Ada tugas baru untuk mata pelajaran {{mata_pelajaran}}:

Judul: {{judul_tugas}}
Batas waktu: {{batas_waktu}}

Lihat detail dan kumpulkan tugas melalui LMS:
{{url_tugas}}
```

## 5. Domain dan Keamanan URL

Domain produksi:

```env
APP_URL=https://lms.didzacorp.com
```

URL tugas dan nilai harus dibuat menggunakan konfigurasi aplikasi, bukan hardcode. Semua tautan produksi wajib menggunakan HTTPS dan tidak boleh menggunakan domain `.test` atau protokol `http://`.

Jangan memasukkan data sensitif, password, atau nilai lengkap ke dalam query string URL WhatsApp.

## 6. Rancangan Antarmuka Guru

Pada halaman rekap pengumpulan tugas, tampilkan:

- nama siswa;
- kelas;
- mata pelajaran;
- judul tugas;
- batas waktu;
- jumlah hari keterlambatan;
- status pengumpulan;
- nomor WhatsApp;
- status izin WhatsApp;
- status pengingat terakhir.

Filter yang diperlukan:

- kelas;
- mata pelajaran;
- tugas;
- belum mengumpulkan;
- terlambat;
- belum pernah diingatkan.

Aksi:

- `Kirim WhatsApp`;
- `Tandai sudah diingatkan`;
- `Lihat detail tugas`.

## 7. Alur Pengiriman Gratis

1. Guru membuka rekap tugas.
2. Guru memfilter siswa yang belum mengumpulkan.
3. Sistem menghitung jumlah hari keterlambatan.
4. Guru memilih satu siswa.
5. Sistem membuat pesan personal.
6. Sistem membuka WhatsApp atau WhatsApp Web melalui tautan `wa.me`.
7. Guru memeriksa isi pesan.
8. Guru menekan tombol kirim di WhatsApp.
9. Guru menandai pengingat sebagai sudah dikirim.

Pengiriman banyak siswa tetap dilakukan satu per satu. Satu klik dapat menyiapkan dan membuka pesan, tetapi tidak dapat mengirim otomatis ke banyak nomor tanpa API resmi.

## 8. Komponen Backend yang Direncanakan

- `WhatsAppMessageTemplate` untuk template pesan.
- `WhatsAppMessageLog` untuk riwayat pengingat.
- `WhatsAppService` untuk normalisasi nomor dan pembuatan tautan.
- Policy untuk memastikan guru hanya menghubungi siswa yang berwenang.
- Helper perhitungan keterlambatan berdasarkan tenggat tugas.
- Validasi persetujuan notifikasi siswa.

Contoh format tautan:

```text
https://wa.me/628xxxxxxxxxx?text={{pesan_url_encoded}}
```

## 9. Aturan Privasi

- Hanya siswa yang memberikan nomor dan persetujuan yang dapat menerima notifikasi.
- Nomor telepon dan persetujuan menerima informasi tugas/pengingat WhatsApp wajib
  dilengkapi sebelum siswa dapat mengakses menu LMS. Persetujuan harus diberikan
  sendiri oleh siswa; tidak diaktifkan otomatis oleh sistem.
- Guru hanya dapat melihat siswa dari kelas atau tugas yang menjadi tanggung jawabnya.
- Pesan dibuat ringkas dan tidak memuat data akademik yang berlebihan.
- Log menyimpan waktu, penerima, jenis template, dan pengguna yang memulai pengingat.
- Jangan menyimpan token WhatsApp karena MVP tidak menggunakan API.

## 10. Tahapan Implementasi

### Tahap A — Data dan konfigurasi

- [x] Tambahkan kolom nomor WhatsApp siswa.
- [x] Tambahkan status persetujuan dan waktu persetujuan.
- [x] Tambahkan validasi dan normalisasi nomor.
- [x] Wajibkan siswa mengisi nomor dan menyetujui WhatsApp melalui Pengaturan Akun.
- [ ] Pastikan `APP_URL` produksi benar sebelum tautan pengingat dirilis.

#### Implementasi awal (12 September 2026)

- Nomor disimpan di `siswa.nomor_whatsapp`; data lama tetap kosong sampai siswa
  mengisinya sendiri. Tidak ada nomor atau persetujuan yang dibuat otomatis.
- Route siswa selain pengaturan/profil meminta nomor berformat valid dan persetujuan. Pengaturan
  password/foto dan logout tetap tersedia. Akun tanpa record siswa diminta
  menghubungi administrator, bukan dibuatkan record siswa kosong.
- Form kontak terpisah dari form password: `PUT /siswa/pengaturan/telepon`.
  Endpoint hanya memperbarui record siswa milik pengguna yang login.
- Format `08`, `628`, dan `+628` diterima, termasuk pemisah spasi/tanda hubung/
  kurung, lalu disimpan sebagai `628...`. Nomor kosong/tidak valid ditolak.
- Persetujuan WhatsApp wajib sesuai keputusan implementasi. Form dan backend
  menolak penyimpanan tanpa persetujuan; nomor valid saja belum membuka akses LMS.
  Checkbox akun baru tetap tidak dicentang sampai siswa menyetujuinya sendiri.
  `whatsapp_opted_in_at` dicatat saat menyetujui. Penyimpanan ulang nomor yang sama
  mempertahankan waktunya; pergantian nomor mencatat waktu persetujuan baru.
- Validasi ini hanya memeriksa format nomor seluler Indonesia, bukan kepemilikan
  nomor atau status terdaftar di WhatsApp. Belum ada OTP, pengiriman pesan,
  preferensi per jenis notifikasi, atau integrasi `wa.me` pada tahap ini.
- Migrasi: `2026_09_12_000001_add_whatsapp_contact_to_siswa.php`.
  Jalankan migrasi sebelum merilis kode ke instalasi lain. `APP_URL` instalasi
  demo tidak diganti menjadi domain produksi dalam tahap pengisian kontak ini.
- Verifikasi tahap awal: 87 test PHP (843 assertion), 14 browser test,
  typecheck, build, Pint, cache Blade, dan pemeriksaan diff lulus. Browser test
  mencakup persetujuan wajib, normalisasi nomor, akses setelah melengkapi kontak,
  serta tampilan pengaturan pada lebar 1440px dan 390px.

### Tahap B — Template dan service

- [x] Buat template pesan keterlambatan dengan fallback aman.
- [x] Buat service pembentuk pesan.
- [x] Buat pembentuk tautan `wa.me`.
- [x] Tambahkan sanitasi dan URL encoding.
- [x] Sediakan override template melalui Pengaturan Sistem.

### Tahap C — Rekap keterlambatan

- [x] Buat query siswa yang belum mengumpulkan.
- [x] Hitung hari keterlambatan.
- [ ] Tambahkan filter khusus belum mengumpulkan/terlambat.
- [x] Tampilkan waktu pengingat terakhir pada baris siswa.

### Tahap D — Integrasi antarmuka

- [x] Tambahkan tombol WhatsApp pada rekap tugas.
- [ ] Tambahkan tombol pada detail siswa.
- [ ] Tambahkan tombol pada halaman nilai.
- [x] Tambahkan aksi tandai sudah diingatkan.

### Tahap E — Pengujian

- [x] Nomor kosong, format `08`, nomor tidak valid, dan persetujuan belum diberikan.
- [x] Siswa belum mengumpulkan dan keterlambatan beberapa hari.
- [x] Hak akses guru terhadap siswa.
- [ ] Tambahkan pengujian karakter khusus/emoji dan verifikasi domain produksi.
- [ ] Tambahkan pengujian tombol pada detail siswa dan halaman nilai.

## Status Audit 12 September 2026

Alur MVP yang saat ini aktif adalah: guru memilih siswa terlambat, LMS menyiapkan
pesan personal, membuka `wa.me`, lalu guru menandai pesan sudah dikirim. Integrasi
ini belum mengirim pesan melalui WhatsApp Business Cloud API dan belum memiliki
OTP/verifikasi kepemilikan nomor, webhook status terkirim, atau pengiriman otomatis.
Sebelum rilis ke instalasi lain, jalankan seluruh migrasi dan pastikan `APP_URL`
bernilai HTTPS yang benar.

Checklist deploy domain utama:

```bash
APP_URL=https://lms.didzacorp.com
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
npm ci
npm run build
```

Verifikasi endpoint pengingat menggunakan akun guru setelah deploy. Jangan
menyalin `.env` demo ke domain utama tanpa mengganti `APP_URL`, kredensial
database, dan cookie/session domain.

## 11. Kriteria Selesai MVP

- Guru dapat melihat daftar siswa yang belum mengumpulkan tugas.
- Hari keterlambatan dihitung otomatis dan akurat.
- Pesan personal terbentuk dari template.
- Tombol membuka chat siswa dengan pesan yang telah terisi.
- Link tugas dan nilai menggunakan HTTPS production domain.
- Guru dapat menandai pengingat yang telah dikirim.
- Tidak ada pengiriman otomatis atau ketergantungan API berbayar.
- Test akses, validasi nomor, dan pembentukan tautan lulus.

## 12. Pengembangan Berikutnya

Jika MVP sudah stabil, fitur dapat ditingkatkan menjadi:

- pengingat terjadwal;
- notifikasi otomatis;
- webhook status pesan;
- WhatsApp Business Cloud API;
- dashboard delivery rate;
- pengaturan batas jumlah pengingat;
- integrasi nomor orang tua secara opsional.
