# Frontend Contrast Checklist

Checklist ini dipakai saat mengubah tema warna frontend LMS. Fokusnya memastikan tema tetap terbaca pada layout Bootstrap + Vite tanpa mengganggu pagination dan komponen form.

## Tema Hijau

- [ ] Sidebar text terbaca di atas background hijau.
- [ ] Topbar title dan role label terbaca.
- [ ] Button primary/success jelas pada default, hover, dan disabled.
- [ ] Link dan pagination aktif terlihat jelas.
- [ ] Badge success, warning, danger, info tetap terbaca.
- [ ] Focus ring input terlihat pada background putih.

## Tema Biru Azure

- [ ] Sidebar text terbaca di atas background biru.
- [ ] Topbar title dan role label terbaca.
- [ ] Button primary/success jelas pada default, hover, dan disabled.
- [ ] Link dan pagination aktif terlihat jelas.
- [ ] Badge success, warning, danger, info tetap terbaca.
- [ ] Focus ring input terlihat pada background putih.

## Tema Biru Aqua

- [ ] Sidebar text terbaca di atas background aqua.
- [ ] Topbar title dan role label terbaca.
- [ ] Button primary/success jelas pada default, hover, dan disabled.
- [ ] Link dan pagination aktif terlihat jelas.
- [ ] Badge success, warning, danger, info tetap terbaca.
- [ ] Focus ring input terlihat pada background putih.

## Area Wajib Dicek

- [ ] Login.
- [ ] Dashboard admin.
- [ ] Kelas & Siswa.
- [ ] Tabel dengan pagination Laravel.
- [ ] Tabel dengan DataTables.
- [ ] Form input nilai atau absensi.
- [ ] Modal edit.
- [ ] Alert success/error/warning.
- [ ] Toast dan confirm dialog.

## Catatan Pagination

- Jangan override markup pagination Laravel.
- Jangan mengganti class `.pagination`, `.page-item`, atau `.page-link` di Blade.
- Override visual cukup lewat CSS variables Bootstrap dan selector yang sudah ada.
- Jika pagination terlihat rusak setelah build Vite, cek urutan load CSS: Bootstrap dari Vite harus lebih dulu, `public/css/lms-app.css` setelahnya.
