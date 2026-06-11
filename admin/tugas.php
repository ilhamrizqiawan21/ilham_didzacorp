<?php
ob_start();
include '../config.php';
include '../includes/fungsi.php';

function update_nilai_sum($conn, $siswa_id, $kategori, $nilai, $semester, $tahun_ajaran) {
    if (empty($kategori) || !is_numeric($nilai)) return false;
    $kolom = strtolower($kategori);
    $valid_columns = ['sum1', 'sum2', 'sum3', 'sum4', 'sts', 'sas_asli', 'sas_jadi', 'sat_asli', 'sat_jadi'];
    if (!in_array($kolom, $valid_columns)) return false;
    
    $cek = mysqli_query($conn, "SELECT id FROM nilai_akhir WHERE siswa_id=$siswa_id AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'");
    if (mysqli_num_rows($cek)) {
        $query = "UPDATE nilai_akhir SET $kolom = $nilai WHERE siswa_id=$siswa_id AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'";
        return mysqli_query($conn, $query);
    } else {
        $query = "INSERT INTO nilai_akhir (siswa_id, semester, tahun_ajaran, $kolom) VALUES ($siswa_id, '$semester', '$tahun_ajaran', $nilai)";
        return mysqli_query($conn, $query);
    }
}

function cek_duplikasi_tugas($conn, $kategori, $semester, $tahun_ajaran, $kelas_ids, $exclude_id = 0) {
    if (empty($kelas_ids)) return false;
    $query = "SELECT t.id, t.judul, GROUP_CONCAT(DISTINCT k.nama_kelas SEPARATOR ', ') as kelas_list
              FROM tugas t
              JOIN tugas_kelas tk ON t.id = tk.tugas_id
              JOIN kelas k ON tk.kelas_id = k.id
              WHERE t.kategori_nilai = '$kategori'
                AND t.semester = '$semester'
                AND t.tahun_ajaran = '$tahun_ajaran'
                AND tk.kelas_id IN (" . implode(',', $kelas_ids) . ")
                AND t.id != $exclude_id
              GROUP BY t.id";
    $res = mysqli_query($conn, $query);
    return ($res && mysqli_num_rows($res) > 0) ? mysqli_fetch_assoc($res) : false;
}

// PROSES SIMPAN TUGAS BARU
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_tugas'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $mulai = mysqli_real_escape_string($conn, $_POST['durasi_mulai']);
    $selesai = mysqli_real_escape_string($conn, $_POST['durasi_selesai']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori_nilai']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $tahun_ajaran = mysqli_real_escape_string($conn, $_POST['tahun_ajaran']);
    
    $error_msg = '';
    $success_msg = '';
    
    if (empty($kategori)) {
        $error_msg = "Kategori nilai harus dipilih!";
    } else {
        $kelas_ids = isset($_POST['kelas_id']) && is_array($_POST['kelas_id']) ? array_map('intval', $_POST['kelas_id']) : [];
        $duplicate = cek_duplikasi_tugas($conn, $kategori, $semester, $tahun_ajaran, $kelas_ids);
        if ($duplicate) {
            $error_msg = "Gagal! Sudah ada tugas dengan kategori $kategori untuk kelas {$duplicate['kelas_list']} pada semester $semester tahun $tahun_ajaran. Tugas tersebut berjudul: \"{$duplicate['judul']}\".";
        } else {
            $query = "INSERT INTO tugas (judul, jenis, deskripsi, durasi_mulai, durasi_selesai, kategori_nilai, semester, tahun_ajaran) 
                      VALUES ('$judul', '$jenis', '$deskripsi', '$mulai', '$selesai', '$kategori', '$semester', '$tahun_ajaran')";
            if (mysqli_query($conn, $query)) {
                $tugas_id = mysqli_insert_id($conn);
                foreach ($kelas_ids as $kelas_id) {
                    mysqli_query($conn, "INSERT INTO tugas_kelas (tugas_id, kelas_id) VALUES ($tugas_id, $kelas_id)");
                }
                if (isset($_POST['siswa_id']) && is_array($_POST['siswa_id'])) {
                    foreach ($_POST['siswa_id'] as $siswa_id) {
                        $status = isset($_POST['status'][$siswa_id]) ? 'sudah' : 'belum';
                        $nilai = isset($_POST['nilai'][$siswa_id]) && $_POST['nilai'][$siswa_id] !== '' ? (float)$_POST['nilai'][$siswa_id] : null;
                        mysqli_query($conn, "INSERT INTO pengumpulan_tugas (tugas_id, siswa_id, status, nilai) 
                                             VALUES ($tugas_id, $siswa_id, '$status', " . ($nilai !== null ? $nilai : 'NULL') . ")");
                        if ($status == 'sudah' && $nilai !== null) {
                            update_nilai_sum($conn, $siswa_id, $kategori, $nilai, $semester, $tahun_ajaran);
                        }
                    }
                }
                $success_msg = "Tugas berhasil ditambahkan.";
            } else {
                $error_msg = "Gagal menyimpan tugas: " . mysqli_error($conn);
            }
        }
    }
    
    $redirect = "tugas";
    if (!empty($error_msg)) $redirect .= "?error=" . urlencode($error_msg);
    if (!empty($success_msg)) $redirect .= "?success=" . urlencode($success_msg);
    header("Location: $redirect");
    exit;
}

// PROSES UPDATE TUGAS (EDIT)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_tugas'])) {
    $tugas_id = (int)$_POST['tugas_id'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $jenis = mysqli_real_escape_string($conn, $_POST['jenis']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $mulai = mysqli_real_escape_string($conn, $_POST['durasi_mulai']);
    $selesai = mysqli_real_escape_string($conn, $_POST['durasi_selesai']);
    $kategori = mysqli_real_escape_string($conn, $_POST['kategori_nilai']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $tahun_ajaran = mysqli_real_escape_string($conn, $_POST['tahun_ajaran']);
    $kelas_ids = isset($_POST['kelas_id']) && is_array($_POST['kelas_id']) ? array_map('intval', $_POST['kelas_id']) : [];
    
    $error_msg = '';
    $success_msg = '';
    
    $duplicate = cek_duplikasi_tugas($conn, $kategori, $semester, $tahun_ajaran, $kelas_ids, $tugas_id);
    if ($duplicate) {
        $error_msg = "Gagal update! Sudah ada tugas lain dengan kategori $kategori untuk kelas {$duplicate['kelas_list']} pada semester $semester tahun $tahun_ajaran. Tugas tersebut berjudul: \"{$duplicate['judul']}\".";
    } else {
        $update_query = "UPDATE tugas SET 
            judul='$judul',
            jenis='$jenis',
            deskripsi='$deskripsi',
            durasi_mulai='$mulai',
            durasi_selesai='$selesai',
            kategori_nilai='$kategori',
            semester='$semester',
            tahun_ajaran='$tahun_ajaran'
            WHERE id=$tugas_id";
        if (mysqli_query($conn, $update_query)) {
            mysqli_query($conn, "DELETE FROM tugas_kelas WHERE tugas_id=$tugas_id");
            foreach ($kelas_ids as $kelas_id) {
                mysqli_query($conn, "INSERT INTO tugas_kelas (tugas_id, kelas_id) VALUES ($tugas_id, $kelas_id)");
            }
            $success_msg = "Tugas berhasil diupdate.";
        } else {
            $error_msg = "Gagal update tugas: " . mysqli_error($conn);
        }
    }
    
    $redirect = "tugas.php";
    if (!empty($error_msg)) $redirect .= "?error=" . urlencode($error_msg);
    if (!empty($success_msg)) $redirect .= "?success=" . urlencode($success_msg);
    header("Location: $redirect");
    exit;
}

// PROSES UPDATE NILAI TUGAS (dengan komentar)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_nilai_tugas'])) {
    $tugas_id = (int)$_POST['tugas_id'];
    $data_tugas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT kategori_nilai, semester, tahun_ajaran FROM tugas WHERE id=$tugas_id"));
    $kategori = $data_tugas['kategori_nilai'];
    $semester = $data_tugas['semester'];
    $tahun_ajaran = $data_tugas['tahun_ajaran'];
    
    $error_msg = '';
    $success_msg = '';
    
    if (empty($kategori)) {
        $error_msg = "Error: Kategori nilai tidak valid!";
    } else {
        foreach ($_POST['status'] as $siswa_id => $status) {
            $nilai = isset($_POST['nilai'][$siswa_id]) && $_POST['nilai'][$siswa_id] !== '' ? (float)$_POST['nilai'][$siswa_id] : null;
            
            if ($nilai !== null) {
                $status_db = 'sudah';
            } else {
                $status_db = $status == 'sudah' ? 'sudah' : 'belum';
            }
            
            $catatan = isset($_POST['catatan'][$siswa_id]) ? mysqli_real_escape_string($conn, $_POST['catatan'][$siswa_id]) : '';
            
            $cek = mysqli_query($conn, "SELECT id FROM pengumpulan_tugas WHERE tugas_id=$tugas_id AND siswa_id=$siswa_id");
            if (mysqli_num_rows($cek)) {
                $query = "UPDATE pengumpulan_tugas SET status='$status_db', nilai=" . ($nilai !== null ? $nilai : 'NULL') . ", catatan='$catatan' WHERE tugas_id=$tugas_id AND siswa_id=$siswa_id";
            } else {
                $query = "INSERT INTO pengumpulan_tugas (tugas_id, siswa_id, status, nilai, catatan) VALUES ($tugas_id, $siswa_id, '$status_db', " . ($nilai !== null ? $nilai : 'NULL') . ", '$catatan')";
            }
            
            if (!mysqli_query($conn, $query)) {
                $error_msg = "Gagal update untuk siswa ID $siswa_id: " . mysqli_error($conn);
                break;
            }
            
            if ($nilai !== null) {
                update_nilai_sum($conn, $siswa_id, $kategori, $nilai, $semester, $tahun_ajaran);
            }
        }
        if (empty($error_msg)) $success_msg = "Nilai dan komentar berhasil diupdate.";
    }
    
    $redirect = "tugas.php";
    if (!empty($error_msg)) $redirect .= "?error=" . urlencode($error_msg);
    if (!empty($success_msg)) $redirect .= "?success=" . urlencode($success_msg);
    header("Location: $redirect");
    exit;
}

// PROSES HAPUS TUGAS
if (isset($_GET['hapus_tugas'])) {
    $id = (int)$_GET['hapus_tugas'];
    mysqli_query($conn, "DELETE FROM tugas_kelas WHERE tugas_id=$id");
    mysqli_query($conn, "DELETE FROM pengumpulan_tugas WHERE tugas_id=$id");
    mysqli_query($conn, "DELETE FROM tugas WHERE id=$id");
    header("Location: tugas?success=" . urlencode("Tugas dihapus."));
    exit;
}

// AMBIL PESAN DARI URL
$error_msg = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success_msg = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$title = 'Penugasan & Nilai SUM';
include '../includes/header.php';

$tahun_aktif = get_tahun_ajaran_aktif($conn);
$semester_aktif = get_semester_aktif($conn);
if (!$tahun_aktif) $tahun_aktif = date('Y') . '/' . (date('Y')+1);
if (!$semester_aktif) $semester_aktif = (date('m') >= 7) ? '1' : '2';

$all_kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas");
$kelas_list = mysqli_query($conn, "SELECT k.id, k.nama_kelas FROM kelas k ORDER BY k.nama_kelas");
$tugas_list = mysqli_query($conn, "SELECT t.*, 
    GROUP_CONCAT(DISTINCT k.id) as kelas_ids,
    GROUP_CONCAT(DISTINCT k.nama_kelas SEPARATOR ', ') as target_kelas 
    FROM tugas t 
    LEFT JOIN tugas_kelas tk ON t.id = tk.tugas_id 
    LEFT JOIN kelas k ON tk.kelas_id = k.id 
    GROUP BY t.id ORDER BY t.created_at DESC");
?>

<style>
/* ========== PERBAIKAN TAMPILAN TUGAS.PHP ========== */
/* Global */
:root {
    --primary-light: #ecfdf5;
    --primary-dark: #047857;
}

/* Alert */
.alert-error, .alert-success {
    padding: 12px 16px;
    margin-bottom: 20px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
}
.alert-error {
    background-color: #fee2e2;
    color: #b91c1c;
    border-left: 4px solid #dc2626;
}
.alert-success {
    background-color: #dcfce7;
    color: #166534;
    border-left: 4px solid #22c55e;
}

/* Card tugas */
.tugas-card {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    padding: 1rem;
    margin-bottom: 1rem;
    background: white;
    transition: box-shadow 0.2s;
}
.tugas-card:hover {
    box-shadow: var(--shadow-md);
}

/* Tabel nilai tugas */
.nilai-tugas-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.75rem;
}
.nilai-tugas-table th, 
.nilai-tugas-table td {
    border: 1px solid #e2e8f0;
    padding: 0.5rem;
    vertical-align: middle;
}
.nilai-tugas-table th {
    background-color: #f1f5f9;
    font-weight: 600;
    text-align: center;
}
.nilai-tugas-table input[type="checkbox"] {
    transform: scale(1);
    margin: 0;
    width: 18px;
    height: 18px;
}
.nilai-tugas-table input[type="number"] {
    width: 70px;
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
    text-align: center;
    border-radius: 0.5rem;
    border: 1px solid #cbd5e1;
}
/* Kolom komentar guru - style lebih bagus */
.nilai-tugas-table textarea {
    font-size: 0.7rem;
    padding: 0.5rem;
    height: 60px;
    resize: vertical;
    width: 100%;
    border-radius: 0.75rem;
    border: 1px solid #cbd5e1;
    background-color: #fef9e3;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    line-height: 1.4;
    transition: all 0.2s;
}
.nilai-tugas-table textarea:focus {
    outline: none;
    border-color: var(--primary-500);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    background-color: #ffffff;
}

/* Tombol */
.btn-icon {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.3rem 0.8rem;
    border-radius: 2rem;
    font-size: 0.7rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
    border: none;
    cursor: pointer;
}
.btn-edit {
    background-color: #eef2ff;
    color: #4338ca;
    border: 1px solid #c7d2fe;
}
.btn-edit:hover {
    background-color: #4338ca;
    color: white;
}
.btn-delete {
    background-color: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}
.btn-delete:hover {
    background-color: #dc2626;
    color: white;
}
.btn-info {
    background-color: #9ca3af;
    color: white;
    border: none;
    border-radius: 2rem;
    padding: 0.2rem 0.6rem;
    font-size: 0.6rem;
}
.btn-info:hover {
    background-color: #6b7280;
}

/* Modal */
.nilaimodal {
    position: fixed;
    top:0; left:0;
    width:100%; height:100%;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(3px);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    visibility: hidden;
    opacity: 0;
    transition: 0.2s;
}
.nilaimodal.active {
    visibility: visible;
    opacity: 1;
}
.modal-content {
    background: white;
    border-radius: 1.25rem;
    max-width: 700px;
    width: 90%;
    max-height: 85vh;
    overflow-y: auto;
    padding: 1.25rem;
    box-shadow: var(--shadow-xl);
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    padding-bottom: 0.75rem;
    margin-bottom: 1rem;
}
.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #64748b;
}
.modal-close:hover {
    color: #dc2626;
}

/* Responsif */
@media (max-width: 768px) {
    .nilai-tugas-table th, .nilai-tugas-table td {
        padding: 0.3rem;
        font-size: 0.65rem;
    }
    .nilai-tugas-table input[type="number"] {
        width: 55px;
    }
    .nilai-tugas-table textarea {
        height: 50px;
        font-size: 0.65rem;
    }
    .tugas-card h3 {
        font-size: 0.9rem;
    }
}
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-tasks"></i> Penugasan & Nilai SUM</h2>
    <p class="page-subtitle">Buat tugas baru, edit, hapus. Berikan nilai dan komentar untuk setiap siswa.</p>
</div>

<?php if ($error_msg): ?>
    <div class="alert-error"><?= $error_msg ?></div>
<?php endif; ?>
<?php if ($success_msg): ?>
    <div class="alert-success"><?= $success_msg ?></div>
<?php endif; ?>

<!-- Form Tambah Tugas Baru -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-plus-circle"></i> Tambah Tugas Baru</div>
    <form method="POST" id="formTugasBaru">
        <div class="form-row">
            <div class="form-group"><label>Judul Tugas</label><input type="text" name="judul" class="form-input" required></div>
            <div class="form-group"><label>Jenis</label><select name="jenis" class="form-select"><option value="mandiri">Mandiri</option><option value="kelompok">Kelompok</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Durasi Mulai</label><input type="date" name="durasi_mulai" class="form-input"></div>
            <div class="form-group"><label>Durasi Selesai</label><input type="date" name="durasi_selesai" class="form-input"></div>
            <div class="form-group"><label>Kategori Nilai</label><select name="kategori_nilai" class="form-select" required><option value="">-- Pilih --</option><option value="SUM1">SUM1</option><option value="SUM2">SUM2</option><option value="SUM3">SUM3</option><option value="SUM4">SUM4</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Semester</label><select name="semester" class="form-select"><option value="1">Semester 1</option><option value="2" selected>Semester 2</option></select></div>
            <div class="form-group"><label>Tahun Ajaran</label><select name="tahun_ajaran" class="form-select"><?php foreach(['2024/2025','2025/2026','2026/2027','2027/2028'] as $ta): ?><option value="<?= $ta?>" <?= $tahun_aktif==$ta?'selected':''?>><?=$ta?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-group"><label>Kelas Sasaran</label><div style="display:flex;flex-wrap:wrap;gap:10px;"><?php $all_kelas2 = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas"); while($k=mysqli_fetch_assoc($all_kelas2)): ?><label><input type="checkbox" name="kelas_id[]" value="<?=$k['id']?>"> <?=htmlspecialchars($k['nama_kelas'])?></label><?php endwhile; ?></div></div>
        <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" class="form-textarea" rows="3"></textarea></div>
        <div class="form-title"><i class="fas fa-users"></i> Input Nilai Awal (Opsional)</div>
        <div class="form-row"><div class="form-group"><label>Filter Kelas</label><select id="filterKelasTugas" class="form-select"><option value="0">-- Pilih Kelas --</option><?php while($k=mysqli_fetch_assoc($kelas_list)): ?><option value="<?=$k['id']?>"><?=htmlspecialchars($k['nama_kelas'])?></option><?php endwhile; ?></select></div></div>
        <div id="siswaTableContainer" style="display:none;"><div class="table-wrapper"><table class="modern-table nilai-tugas-table" id="siswaTable"><thead><tr><th>NIS</th><th>Nama</th><th>Sudah Selesai</th><th>Nilai (0-100)</th></tr></thead><tbody></tbody></table></div></div>
        <button type="submit" name="simpan_tugas" class="btn btn-primary">Simpan Tugas & Nilai</button>
    </form>
</div>

<!-- Daftar Tugas -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-list"></i> Daftar Tugas</div>
    <?php if (mysqli_num_rows($tugas_list) == 0): ?>
        <p>Belum ada tugas.</p>
    <?php else: ?>
        <?php while($t = mysqli_fetch_assoc($tugas_list)): 
            $query_target_kelas = "SELECT k.id, k.nama_kelas FROM tugas_kelas tk JOIN kelas k ON tk.kelas_id = k.id WHERE tk.tugas_id = {$t['id']} ORDER BY k.nama_kelas";
            $target_kelas_res = mysqli_query($conn, $query_target_kelas);
            $target_options = '<option value="0">-- Pilih Kelas --</option>';
            while($kelas_target = mysqli_fetch_assoc($target_kelas_res)) $target_options .= '<option value="'.$kelas_target['id'].'">'.htmlspecialchars($kelas_target['nama_kelas']).'</option>';
        ?>
            <div class="tugas-card">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
                    <h3 style="font-size:1rem;"><?= htmlspecialchars($t['judul']) ?> (<?= $t['kategori_nilai'] ?: 'Kategori belum diisi' ?>) - <?= $t['jenis'] ?></h3>
                    <div class="action-buttons">
                        <button class="btn-icon btn-edit" data-tugas='<?= json_encode($t) ?>' onclick="editTugas(this)"><i class="fas fa-edit"></i> Edit</button>
                        <a href="?hapus_tugas=<?= $t['id'] ?>" class="btn-icon btn-delete" onclick="return confirm('Hapus tugas?')"><i class="fas fa-trash-alt"></i> Hapus</a>
                    </div>
                </div>
                <p style="font-size:0.75rem; margin-top:0.5rem;"><strong>Deskripsi:</strong> <?= nl2br(htmlspecialchars($t['deskripsi'])) ?></p>
                <p style="font-size:0.75rem;"><strong>Durasi:</strong> <?= $t['durasi_mulai'] ?> s.d. <?= $t['durasi_selesai'] ?></p>
                <p style="font-size:0.75rem;"><strong>Semester:</strong> <?= $t['semester'] ?> | <strong>Tahun Ajaran:</strong> <?= $t['tahun_ajaran'] ?></p>
                <p style="font-size:0.75rem;"><strong>Kelas Sasaran:</strong> <?= $t['target_kelas'] ?: 'Semua Kelas' ?></p>
                
                <form method="POST">
                    <input type="hidden" name="tugas_id" value="<?= $t['id'] ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label style="font-size:0.7rem;">Pilih Kelas (untuk update nilai)</label>
                            <select name="kelas_filter" class="form-select" style="font-size:0.7rem; padding:4px;" onchange="loadSiswaForTugas(this.value, <?= $t['id'] ?>)">
                                <?= $target_options ?>
                            </select>
                        </div>
                    </div>
                    <div id="siswaContainer_<?= $t['id'] ?>"></div>
                    <button type="submit" name="update_nilai_tugas" class="btn btn-primary" style="padding:4px 12px; font-size:0.7rem; margin-top:0.5rem;">Update Nilai & Komentar</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<!-- Modal Edit Tugas -->
<div id="editModal" class="nilaimodal">
    <div class="modal-content">
        <div class="modal-header"><h3>Edit Tugas</h3><button class="modal-close" onclick="closeEditModal()">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="tugas_id" id="edit_tugas_id">
            <div class="form-group"><label>Judul</label><input type="text" name="judul" id="edit_judul" class="form-input" required></div>
            <div class="form-group"><label>Jenis</label><select name="jenis" id="edit_jenis" class="form-select"><option value="mandiri">Mandiri</option><option value="kelompok">Kelompok</option></select></div>
            <div class="form-row"><div class="form-group"><label>Durasi Mulai</label><input type="date" name="durasi_mulai" id="edit_durasi_mulai" class="form-input"></div><div class="form-group"><label>Durasi Selesai</label><input type="date" name="durasi_selesai" id="edit_durasi_selesai" class="form-input"></div></div>
            <div class="form-row"><div class="form-group"><label>Kategori Nilai</label><select name="kategori_nilai" id="edit_kategori" class="form-select"><option value="">-- Pilih --</option><option value="SUM1">SUM1</option><option value="SUM2">SUM2</option><option value="SUM3">SUM3</option><option value="SUM4">SUM4</option></select></div></div>
            <div class="form-row"><div class="form-group"><label>Semester</label><select name="semester" id="edit_semester" class="form-select"><option value="1">Semester 1</option><option value="2">Semester 2</option></select></div><div class="form-group"><label>Tahun Ajaran</label><select name="tahun_ajaran" id="edit_tahun_ajaran" class="form-select"><?php foreach(['2024/2025','2025/2026','2026/2027','2027/2028'] as $ta): ?><option value="<?=$ta?>"><?=$ta?></option><?php endforeach; ?></select></div></div>
            <div class="form-group"><label>Kelas Sasaran</label><div id="edit_kelas_checkboxes" style="display:flex;flex-wrap:wrap;gap:10px;"><?php $all_kelas_edit = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas"); while($k=mysqli_fetch_assoc($all_kelas_edit)): ?><label><input type="checkbox" name="kelas_id[]" value="<?=$k['id']?>" class="edit-kelas-checkbox"> <?=htmlspecialchars($k['nama_kelas'])?></label><?php endwhile; ?></div></div>
            <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" id="edit_deskripsi" class="form-textarea" rows="3"></textarea></div>
            <div class="modal-footer"><button type="submit" name="edit_tugas" class="btn btn-primary">Simpan Perubahan</button><button type="button" class="btn btn-outline" onclick="closeEditModal()">Batal</button></div>
        </form>
    </div>
</div>

<!-- Modal untuk melihat detail jawaban teks dan file -->
<div id="detailModal" class="nilaimodal">
    <div class="modal-content">
        <div class="modal-header"><h3><i class="fas fa-eye"></i> Detail Jawaban Siswa</h3><button class="modal-close" onclick="closeDetailModal()">&times;</button></div>
        <div class="modal-body" id="detailModalBody" style="white-space: pre-wrap; font-family: monospace;">Memuat...</div>
        <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeDetailModal()">Tutup</button></div>
    </div>
</div>

<script>
// Escape HTML
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;')
              .replace(/</g, '&lt;')
              .replace(/>/g, '&gt;')
              .replace(/"/g, '&quot;')
              .replace(/'/g, '&#39;');
}

// Filter kelas untuk input nilai awal
document.getElementById('filterKelasTugas').addEventListener('change', function() {
    let kelasId = this.value;
    if (kelasId == 0) {
        document.getElementById('siswaTableContainer').style.display = 'none';
        return;
    }
    fetch(`get_siswa_by_kelas?kelas_id=${kelasId}`)
        .then(res => res.json())
        .then(data => {
            let tbody = document.querySelector('#siswaTable tbody');
            tbody.innerHTML = '';
            data.forEach(s => {
                let row = `<tr>
                    <td>${s.nis}</td>
                    <td>${s.nama}</td>
                    <td><input type="checkbox" name="status[${s.id}]" value="sudah"></td>
                    <td><input type="number" name="nilai[${s.id}]" step="0.01" min="0" max="100" class="form-input" style="width:70px"></td>
                </tr>`;
                tbody.insertAdjacentHTML('beforeend', row);
            });
            document.querySelectorAll('#formTugasBaru input[name="siswa_id[]"]').forEach(el => el.remove());
            data.forEach(s => {
                let hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'siswa_id[]';
                hidden.value = s.id;
                document.getElementById('formTugasBaru').appendChild(hidden);
            });
            document.getElementById('siswaTableContainer').style.display = 'block';
        });
});

function loadSiswaForTugas(kelasId, tugasId) {
    if (kelasId == 0) {
        document.getElementById(`siswaContainer_${tugasId}`).innerHTML = '';
        return;
    }
    fetch(`get_siswa_with_tugas?kelas_id=${kelasId}&tugas_id=${tugasId}`)
        .then(res => res.json())
        .then(data => {
            let html = `<div class="table-wrapper">
                <table class="modern-table nilai-tugas-table">
                    <thead>
                        <tr>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Sudah Selesai</th>
                            <th>Nilai</th>
                            <th>File Tugas</th>
                            <th>Jawaban Teks</th>
                            <th>Komentar Guru</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>`;
            data.forEach(s => {
                let checked = (s.nilai !== null && s.nilai !== '') ? 'checked' : (s.status == 'sudah' ? 'checked' : '');
                let nilaiValue = s.nilai ? s.nilai : '';
                let fileLink = s.file_upload ? `<a href="../uploads/tugas_siswa/${s.file_upload}" target="_blank" class="btn-sm" style="background:#f3f4f6; padding:2px 6px; border-radius:4px; font-size:0.6rem;"><i class="fas fa-download"></i> Download</a>` : '-';
                let jawabanPreview = s.teks_jawaban ? `<span>${escapeHtml(s.teks_jawaban.substring(0, 40))}...</span>` : '-';
                let jawabanBtn = s.teks_jawaban ? `<button type="button" class="btn-info" data-teks="${escapeHtml(s.teks_jawaban)}" onclick="showJawaban(this)"><i class="fas fa-eye"></i> Lihat</button>` : '-';
                let fileBtn = s.file_upload ? `<button type="button" class="btn-info" data-file="${escapeHtml(s.file_upload)}" onclick="showFile(this)"><i class="fas fa-eye"></i> File</button>` : '-';
                let aksi = (jawabanBtn !== '-' || fileBtn !== '-') ? `<div style="display:flex; gap:4px;">${jawabanBtn} ${fileBtn}</div>` : '-';
                let catatanValue = s.catatan ? escapeHtml(s.catatan) : '';
                
                html += `<tr>
                    <td>${s.nis}</td>
                    <td>${s.nama}</td>
                    <td><input type="checkbox" name="status[${s.id}]" value="sudah" ${checked} class="status-checkbox" data-siswa="${s.id}"></td>
                    <td><input type="number" name="nilai[${s.id}]" step="0.01" min="0" max="100" class="form-input nilai-input" data-siswa="${s.id}" value="${nilaiValue}" style="width:70px"></td>
                    <td>${fileLink}</td>
                    <td>${jawabanPreview}</td>
                    <td><textarea name="catatan[${s.id}]" rows="2" style="width:100%; font-size:0.7rem; padding:0.5rem; border-radius:0.75rem; border:1px solid #cbd5e1; background-color:#fef9e3;">${catatanValue}</textarea></td>
                    <td>${aksi}</td>
                </tr>`;
            });
            html += `</tbody></table></div>`;
            document.getElementById(`siswaContainer_${tugasId}`).innerHTML = html;
            
            document.querySelectorAll(`#siswaContainer_${tugasId} .nilai-input`).forEach(input => {
                input.addEventListener('change', function() {
                    let siswaId = this.dataset.siswa;
                    let checkbox = document.querySelector(`#siswaContainer_${tugasId} input.status-checkbox[data-siswa="${siswaId}"]`);
                    if (checkbox && this.value.trim() !== '') {
                        checkbox.checked = true;
                    }
                });
            });
        });
}

function showJawaban(btn) {
    let teks = btn.getAttribute('data-teks');
    let modalBody = document.getElementById('detailModalBody');
    modalBody.innerHTML = `<div style="white-space: pre-wrap; font-family: monospace; background: #f9fafb; padding: 15px; border-radius: 8px;">${teks.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>`;
    document.getElementById('detailModal').classList.add('active');
}

function showFile(btn) {
    let filename = btn.getAttribute('data-file');
    let fileUrl = `../uploads/tugas_siswa/${filename}`;
    let ext = filename.split('.').pop().toLowerCase();
    let modalBody = document.getElementById('detailModalBody');
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
        modalBody.innerHTML = `<div style="text-align:center;"><img src="${fileUrl}" style="max-width:100%; max-height:500px; border-radius:8px;"></div>`;
    } else if (ext === 'pdf') {
        modalBody.innerHTML = `<embed src="${fileUrl}" width="100%" height="500px" type="application/pdf">`;
    } else {
        modalBody.innerHTML = `<div style="text-align:center; padding:20px;">
            <p>File tidak dapat dipratinjau langsung.</p>
            <a href="${fileUrl}" class="btn btn-primary" download><i class="fas fa-download"></i> Download File</a>
        </div>`;
    }
    document.getElementById('detailModal').classList.add('active');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('active');
    document.getElementById('detailModalBody').innerHTML = 'Memuat...';
}

function editTugas(button) {
    let tugas = JSON.parse(button.getAttribute('data-tugas'));
    document.getElementById('edit_tugas_id').value = tugas.id;
    document.getElementById('edit_judul').value = tugas.judul;
    document.getElementById('edit_jenis').value = tugas.jenis;
    document.getElementById('edit_deskripsi').value = tugas.deskripsi;
    document.getElementById('edit_durasi_mulai').value = tugas.durasi_mulai;
    document.getElementById('edit_durasi_selesai').value = tugas.durasi_selesai;
    document.getElementById('edit_kategori').value = tugas.kategori_nilai;
    document.getElementById('edit_semester').value = tugas.semester;
    document.getElementById('edit_tahun_ajaran').value = tugas.tahun_ajaran;
    
    let kelas_ids = tugas.kelas_ids ? tugas.kelas_ids.split(',') : [];
    document.querySelectorAll('.edit-kelas-checkbox').forEach(cb => cb.checked = false);
    kelas_ids.forEach(id => {
        let cb = document.querySelector(`.edit-kelas-checkbox[value="${id}"]`);
        if (cb) cb.checked = true;
    });
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}
window.onclick = function(event) {
    let modal = document.getElementById('editModal');
    let detail = document.getElementById('detailModal');
    if (event.target === modal) closeEditModal();
    if (event.target === detail) closeDetailModal();
}
</script>

<?php include '../includes/footer.php'; ob_end_flush(); ?>