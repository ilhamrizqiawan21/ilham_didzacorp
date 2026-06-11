// script.js - Fungsi interaktif untuk website pembelajaran

// ==================== KONFIRMASI HAPUS ====================
function konfirmasiHapus(url, pesan = 'Yakin ingin menghapus data ini?') {
    if (confirm(pesan)) {
        window.location.href = url;
    }
    return false;
}

// Otomatis tambahkan konfirmasi ke semua link dengan class 'hapus'
document.addEventListener('DOMContentLoaded', function() {
    let hapusLinks = document.querySelectorAll('a[href*="hapus"], a.hapus-link');
    hapusLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Yakin ingin menghapus data ini?')) {
                e.preventDefault();
            }
        });
    });
});

// ==================== VALIDASI FORM ====================
function validasiFormAbsensi(form) {
    let inputs = form.querySelectorAll('select[name^="status"]');
    if (inputs.length === 0) {
        alert('Tidak ada data siswa untuk diisi');
        return false;
    }
    return true;
}

// Validasi form bab (judul tidak boleh kosong)
function validasiFormBab(form) {
    let judul = form.querySelector('input[name="judul"]');
    if (!judul.value.trim()) {
        alert('Judul Bab harus diisi');
        judul.focus();
        return false;
    }
    return true;
}

// ==================== MODAL EDIT BAB (digunakan di admin/bab.php) ====================
function editBab(id, judul, tujuan, materi, waktu) {
    // Isi nilai ke modal
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_judul').value = judul;
    document.getElementById('edit_tujuan').value = tujuan;
    document.getElementById('edit_materi').value = materi;
    document.getElementById('edit_waktu').value = waktu;
    // Tampilkan modal
    document.getElementById('editModal').style.display = 'block';
}

function tutupModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Tutup modal jika klik di luar area modal
window.onclick = function(event) {
    let modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// ==================== FITUR TAMBAHAN ====================
// Preview file sebelum upload (opsional)
function previewFile(input, previewId) {
    let preview = document.getElementById(previewId);
    if (!preview) return;
    if (input.files && input.files[0]) {
        let reader = new FileReader();
        reader.onload = function(e) {
            if (input.files[0].type.startsWith('image/')) {
                preview.innerHTML = '<img src="' + e.target.result + '" style="max-width:200px; max-height:150px;">';
            } else {
                preview.innerHTML = '<p>File: ' + input.files[0].name + '</p>';
            }
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = '';
    }
}

// Filter tabel (pencarian sederhana)
function filterTabel(inputId, tabelId) {
    let input = document.getElementById(inputId);
    let filter = input.value.toUpperCase();
    let table = document.getElementById(tabelId);
    let tr = table.getElementsByTagName("tr");
    for (let i = 1; i < tr.length; i++) { // mulai dari baris kedua (header)
        let td = tr[i].getElementsByTagName("td")[0]; // cari di kolom pertama
        if (td) {
            let txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}

// Cetak halaman rekap absensi
function cetakHalaman() {
    window.print();
}

// Export tabel ke Excel (sederhana)
function exportToExcel(tableId, filename = 'rekap_absensi.xls') {
    let table = document.getElementById(tableId);
    let html = table.outerHTML;
    let url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
    let link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
}

// ==================== INISIALISASI ====================
document.addEventListener('DOMContentLoaded', function() {
    // Tambahkan event listener untuk form absensi
    let formAbsensi = document.querySelector('form[action*="absensi.php"]');
    if (formAbsensi) {
        formAbsensi.addEventListener('submit', function(e) {
            if (!validasiFormAbsensi(this)) {
                e.preventDefault();
            }
        });
    }

    // Form bab
    let formTambahBab = document.querySelector('form[action="bab.php"]');
    if (formTambahBab) {
        formTambahBab.addEventListener('submit', function(e) {
            if (!validasiFormBab(this)) {
                e.preventDefault();
            }
        });
    }

    // Tombol cetak jika ada
    let btnCetak = document.getElementById('btnCetak');
    if (btnCetak) {
        btnCetak.addEventListener('click', cetakHalaman);
    }

    // Tombol export Excel jika ada
    let btnExport = document.getElementById('btnExport');
    if (btnExport) {
        btnExport.addEventListener('click', function() {
            exportToExcel('tabelRekap', 'rekap_absensi.xls');
        });
    }
});

// ==================== SMOOTH SCROLL & ENHANCEMENTS ====================
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll untuk semua anchor internal
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Tambahkan class fade-in ke semua stat-card saat muncul (sudah di CSS)
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.stat-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(15px)';
        card.style.transition = 'all 0.4s ease';
        observer.observe(card);
    });
});