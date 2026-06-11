<?php
session_start();
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Program Tahunan (PROTA)';
include '../includes/header.php';

// Tambah error reporting untuk debug sementara (matiikan setelah selesai)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inisialisasi pesan
$error_msg = '';
$success_msg = '';

// Ambil daftar bab untuk dropdown
$bab_list = mysqli_query($conn, "SELECT id, judul_bab FROM bab ORDER BY id");
if (!$bab_list) {
    $error_msg = "Error ambil bab: " . mysqli_error($conn);
}

// ========== PROSES TAMBAH PROTA ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan'])) {
    $bab_id = isset($_POST['bab_id']) ? (int)$_POST['bab_id'] : 0;
    $atp_id = isset($_POST['atp_id']) ? (int)$_POST['atp_id'] : 0;
    $semester = isset($_POST['semester']) ? mysqli_real_escape_string($conn, $_POST['semester']) : '1';
    
    // Validasi
    if (!$bab_id) {
        $error_msg = "Error: Bab tidak dipilih!";
    } elseif (!$atp_id) {
        $error_msg = "Error: ATP tidak dipilih!";
    } else {
        // Ambil data dari tabel atp
        $query_atp = "SELECT alur_tujuan, materi, alokasi_waktu FROM atp WHERE id = $atp_id";
        $res_atp = mysqli_query($conn, $query_atp);
        if (!$res_atp) {
            $error_msg = "Error query ATP: " . mysqli_error($conn);
        } else {
            $atp = mysqli_fetch_assoc($res_atp);
            if (!$atp) {
                $error_msg = "Error: Data ATP tidak ditemukan (ID: $atp_id)";
            } else {
                // Pastikan field tidak kosong
                if (empty(trim($atp['alur_tujuan']))) {
                    $error_msg = "Error: Alur tujuan di ATP masih kosong. Silakan edit ATP terlebih dahulu.";
                } elseif (empty(trim($atp['materi']))) {
                    $error_msg = "Error: Materi pokok di ATP masih kosong. Silakan edit ATP terlebih dahulu.";
                } else {
                    // Siapkan data untuk insert
                    $alur = mysqli_real_escape_string($conn, $atp['alur_tujuan']);
                    $materi = mysqli_real_escape_string($conn, $atp['materi']);
                    $waktu = mysqli_real_escape_string($conn, $atp['alokasi_waktu'] ?? '');
                    
                    $sql = "INSERT INTO prota (bab_id, alur_tujuan, materi_pokok, alokasi_waktu, semester) 
                            VALUES ($bab_id, '$alur', '$materi', '$waktu', '$semester')";
                    if (mysqli_query($conn, $sql)) {
                        $success_msg = "PROTA berhasil ditambahkan!";
                        // Redirect agar form clear (opsional)
                        // header("Location: prota.php?success=1");
                        // exit;
                    } else {
                        $error_msg = "Error insert PROTA: " . mysqli_error($conn);
                    }
                }
            }
        }
    }
    
    // Tampilkan pesan di halaman (tanpa redirect)
}

// ========== PROSES EDIT PROTA ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_simpan'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $bab_id = isset($_POST['bab_id']) ? (int)$_POST['bab_id'] : 0;
    $atp_id = isset($_POST['atp_id']) ? (int)$_POST['atp_id'] : 0;
    $semester = isset($_POST['semester']) ? mysqli_real_escape_string($conn, $_POST['semester']) : '1';
    
    if (!$id || !$bab_id || !$atp_id) {
        $error_msg = "Error: Data tidak lengkap!";
    } else {
        $query_atp = mysqli_query($conn, "SELECT alur_tujuan, materi, alokasi_waktu FROM atp WHERE id = $atp_id");
        $atp = mysqli_fetch_assoc($query_atp);
        if (!$atp) {
            $error_msg = "Error: ATP tidak ditemukan!";
        } else {
            $alur = mysqli_real_escape_string($conn, $atp['alur_tujuan']);
            $materi = mysqli_real_escape_string($conn, $atp['materi']);
            $waktu = mysqli_real_escape_string($conn, $atp['alokasi_waktu'] ?? '');
            
            $sql = "UPDATE prota SET bab_id=$bab_id, alur_tujuan='$alur', materi_pokok='$materi', 
                    alokasi_waktu='$waktu', semester='$semester' WHERE id=$id";
            if (mysqli_query($conn, $sql)) {
                $success_msg = "PROTA berhasil diupdate!";
            } else {
                $error_msg = "Error update PROTA: " . mysqli_error($conn);
            }
        }
    }
}

// ========== PROSES HAPUS PROTA ==========
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id > 0) {
        $sql = "DELETE FROM prota WHERE id=$id";
        if (mysqli_query($conn, $sql)) {
            $success_msg = "PROTA berhasil dihapus!";
        } else {
            $error_msg = "Error hapus PROTA: " . mysqli_error($conn);
        }
    } else {
        $error_msg = "ID tidak valid untuk hapus.";
    }
}

// Ambbil data untuk ditampilkan (dengan filter semester)
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';
$where = $semester_filter ? "WHERE p.semester='$semester_filter'" : "";
$query_data = "SELECT p.*, b.judul_bab FROM prota p LEFT JOIN bab b ON p.bab_id = b.id $where ORDER BY p.semester, p.id";
$data = mysqli_query($conn, $query_data);
if (!$data) {
    $error_msg = "Error ambil data PROTA: " . mysqli_error($conn);
}
?>

<style>
.alert-error { background: #fee2e2; border-left: 4px solid #dc2626; padding: 12px; margin-bottom: 20px; border-radius: 8px; color: #b91c1c; }
.alert-success { background: #dcfce7; border-left: 4px solid #22c55e; padding: 12px; margin-bottom: 20px; border-radius: 8px; color: #166534; }
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-calendar-alt"></i> Program Tahunan (PROTA)</h2>
    <p class="page-subtitle">Data terintegrasi dengan ATP. Pastikan ATP sudah memiliki materi pokok yang lengkap.</p>
</div>

<?php if ($error_msg): ?>
    <div class="alert-error"><?= htmlspecialchars($error_msg) ?></div>
<?php endif; ?>
<?php if ($success_msg): ?>
    <div class="alert-success"><?= htmlspecialchars($success_msg) ?></div>
<?php endif; ?>

<!-- Filter & Export -->
<div class="form-container no-print" style="margin-bottom:1rem;">
    <form method="GET" class="form-row">
        <div class="form-group">
            <label>Filter Semester</label>
            <select name="semester" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Semester</option>
                <option value="1" <?= $semester_filter == '1' ? 'selected' : '' ?>>Semester 1</option>
                <option value="2" <?= $semester_filter == '2' ? 'selected' : '' ?>>Semester 2</option>
            </select>
        </div>
        <div class="form-group">
            <label>&nbsp;</label>
            <div>
                <a href="export_prota_excel?semester=<?= $semester_filter ?>" class="btn btn-outline"><i class="fas fa-file-excel"></i> Export Excel</a>
                <a href="export_prota_html?semester=<?= $semester_filter ?>" class="btn btn-outline" target="_blank"><i class="fas fa-print"></i> Cetak</a>
            </div>
        </div>
    </form>
</div>

<!-- Form Tambah PROTA -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-plus-circle"></i> Tambah Baris PROTA</div>
    <form method="POST" id="formTambahProta">
        <div class="form-row">
            <div class="form-group">
                <label>Bab</label>
                <select name="bab_id" id="bab_id_tambah" class="form-select" required>
                    <option value="">-- Pilih Bab --</option>
                    <?php 
                    if ($bab_list && mysqli_num_rows($bab_list) > 0) {
                        while($b = mysqli_fetch_assoc($bab_list)) {
                            echo '<option value="'.$b['id'].'">'.htmlspecialchars($b['judul_bab']).'</option>';
                        }
                    } else {
                        echo '<option value="">Belum ada bab</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Semester</label>
                <select name="semester" id="semester_tambah" class="form-select">
                    <option value="1">Semester 1</option>
                    <option value="2">Semester 2</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Pilih ATP (Alur Tujuan Pembelajaran)</label>
            <select name="atp_id" id="atp_id_tambah" class="form-select" required>
                <option value="">-- Pilih Bab terlebih dahulu --</option>
            </select>
        </div>
        <div class="form-group">
            <label>Alur Tujuan Pembelajaran</label>
            <textarea id="alur_tujuan_tampil" class="form-textarea" rows="3" readonly style="background:#f3f4f6;"></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Materi Pokok</label>
                <input type="text" id="materi_tampil" class="form-input" readonly style="background:#f3f4f6;">
            </div>
            <div class="form-group">
                <label>Alokasi Waktu</label>
                <input type="text" id="waktu_tampil" class="form-input" readonly style="background:#f3f4f6;">
            </div>
        </div>
        <button type="submit" name="simpan" class="btn btn-primary">Simpan PROTA</button>
    </form>
</div>

<!-- Daftar PROTA -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-list"></i> Data PROTA</div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Bab</th>
                    <th>Alur Tujuan</th>
                    <th>Materi Pokok</th>
                    <th>Waktu</th>
                    <th>Semester</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data && mysqli_num_rows($data) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($data)): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($row['judul_bab']) ?></strong></td>
                        <td><?= nl2br(htmlspecialchars($row['alur_tujuan'])) ?></td>
                        <td><?= htmlspecialchars($row['materi_pokok']) ?></td>
                        <td><?= htmlspecialchars($row['alokasi_waktu']) ?></td>
                        <td><?= $row['semester'] == 1 ? 'Ganjil' : 'Genap' ?></td>
                        <td>
                            <button class="btn btn-sm btn-edit-prota" 
                                    data-id="<?= $row['id'] ?>" 
                                    data-bab="<?= $row['bab_id'] ?>" 
                                    data-semester="<?= $row['semester'] ?>">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i> Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Belum ada data PROTA.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit PROTA -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Edit PROTA</h3><button class="modal-close" onclick="tutupModal()">&times;</button></div>
        <form method="POST" id="formEditProta">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Bab</label>
                    <select name="bab_id" id="edit_bab" class="form-select" required>
                        <?php 
                        $bab_query = mysqli_query($conn, "SELECT id, judul_bab FROM bab ORDER BY id");
                        if ($bab_query) {
                            while($b = mysqli_fetch_assoc($bab_query)) {
                                echo '<option value="'.$b['id'].'">'.htmlspecialchars($b['judul_bab']).'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Pilih ATP</label>
                    <select name="atp_id" id="edit_atp_id" class="form-select" required>
                        <option value="">-- Pilih Bab terlebih dahulu --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Alur Tujuan</label>
                    <textarea id="edit_alur_tampil" class="form-textarea" rows="3" readonly style="background:#f3f4f6;"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Materi Pokok</label>
                        <input type="text" id="edit_materi_tampil" class="form-input" readonly style="background:#f3f4f6;">
                    </div>
                    <div class="form-group">
                        <label>Alokasi Waktu</label>
                        <input type="text" id="edit_waktu_tampil" class="form-input" readonly style="background:#f3f4f6;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester" id="edit_semester" class="form-select">
                        <option value="1">Semester 1</option>
                        <option value="2">Semester 2</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="edit_simpan" class="btn btn-primary">Update</button>
                <button type="button" class="btn" onclick="tutupModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function loadAtp(babId, selectElement, fields) {
        if (!babId) {
            selectElement.innerHTML = '<option value="">-- Pilih Bab terlebih dahulu --</option>';
            if (fields.alur) fields.alur.value = '';
            if (fields.materi) fields.materi.value = '';
            if (fields.waktu) fields.waktu.value = '';
            return;
        }
        fetch(`get_atp_by_bab?bab_id=${babId}`)
            .then(res => res.json())
            .then(data => {
                selectElement.innerHTML = '<option value="">-- Pilih ATP --</option>';
                if (data.length === 0) {
                    selectElement.innerHTML = '<option value="">-- Tidak ada ATP untuk bab ini --</option>';
                    if (fields.alur) fields.alur.value = '';
                    if (fields.materi) fields.materi.value = '';
                    if (fields.waktu) fields.waktu.value = '';
                    return;
                }
                data.forEach(atp => {
                    const option = document.createElement('option');
                    option.value = atp.id;
                    let shortText = (atp.alur_tujuan || '').substring(0, 80) + ((atp.alur_tujuan || '').length > 80 ? '...' : '');
                    option.textContent = shortText;
                    option.setAttribute('data-fulltext', atp.alur_tujuan || '');
                    option.setAttribute('data-materi', atp.materi || '');
                    option.setAttribute('data-waktu', atp.alokasi_waktu || '');
                    selectElement.appendChild(option);
                });
            })
            .catch(err => console.error('Error loading ATP:', err));
    }

    function onAtpSelect(selectElement, fields) {
        const selected = selectElement.options[selectElement.selectedIndex];
        if (selected && selected.value) {
            if (fields.alur) fields.alur.value = selected.getAttribute('data-fulltext') || '';
            if (fields.materi) fields.materi.value = selected.getAttribute('data-materi') || '';
            if (fields.waktu) fields.waktu.value = selected.getAttribute('data-waktu') || '';
        } else {
            if (fields.alur) fields.alur.value = '';
            if (fields.materi) fields.materi.value = '';
            if (fields.waktu) fields.waktu.value = '';
        }
    }

    // Form Tambah
    const babTambah = document.getElementById('bab_id_tambah');
    const atpTambah = document.getElementById('atp_id_tambah');
    const fieldsTambah = {
        alur: document.getElementById('alur_tujuan_tampil'),
        materi: document.getElementById('materi_tampil'),
        waktu: document.getElementById('waktu_tampil')
    };
    if (babTambah) {
        babTambah.addEventListener('change', () => loadAtp(babTambah.value, atpTambah, fieldsTambah));
        atpTambah.addEventListener('change', () => onAtpSelect(atpTambah, fieldsTambah));
        if (babTambah.value) loadAtp(babTambah.value, atpTambah, fieldsTambah);
    }

    // Modal Edit
    const editBab = document.getElementById('edit_bab');
    const editAtp = document.getElementById('edit_atp_id');
    const fieldsEdit = {
        alur: document.getElementById('edit_alur_tampil'),
        materi: document.getElementById('edit_materi_tampil'),
        waktu: document.getElementById('edit_waktu_tampil')
    };
    if (editBab) {
        editBab.addEventListener('change', () => loadAtp(editBab.value, editAtp, fieldsEdit));
        editAtp.addEventListener('change', () => onAtpSelect(editAtp, fieldsEdit));
    }

    // Tombol edit di tabel
    document.querySelectorAll('.btn-edit-prota').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const bab_id = this.dataset.bab;
            const semester = this.dataset.semester;
            const row = this.closest('tr');
            const alurTeks = row.cells[1].innerText.replace(/\n/g, ' ');
            const materiTeks = row.cells[2].innerText;
            const waktuTeks = row.cells[3].innerText;
            
            document.getElementById('edit_id').value = id;
            fieldsEdit.alur.value = alurTeks;
            fieldsEdit.materi.value = materiTeks;
            fieldsEdit.waktu.value = waktuTeks;
            document.getElementById('edit_semester').value = semester;
            
            for (let i = 0; i < editBab.options.length; i++) {
                if (editBab.options[i].value == bab_id) {
                    editBab.selectedIndex = i;
                    break;
                }
            }
            loadAtp(bab_id, editAtp, fieldsEdit);
            document.getElementById('editModal').style.display = 'flex';
        });
    });
});

function tutupModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>