<?php
ob_start();
include '../config.php';
include '../includes/fungsi.php';

// Hanya admin yang bisa akses
if ($_SESSION['role'] != 'admin') {
    header("Location: ../index");
    exit;
}

use PhpOffice\PhpSpreadsheet\IOFactory;

// Inisialisasi variabel pesan
$error_msg = '';
$success_msg = '';

// ========== PROSES TAMBAH/EDIT/HAPUS KELAS ==========
if (isset($_POST['simpan_kelas'])) {
    $id = (int)$_POST['id'];
    $nama_kelas = mysqli_real_escape_string($conn, $_POST['nama_kelas']);
    $tingkat = mysqli_real_escape_string($conn, $_POST['tingkat']);
    if ($id == 0) {
        mysqli_query($conn, "INSERT INTO kelas (nama_kelas, tingkat) VALUES ('$nama_kelas', '$tingkat')");
    } else {
        mysqli_query($conn, "UPDATE kelas SET nama_kelas='$nama_kelas', tingkat='$tingkat' WHERE id=$id");
    }
    header("Location: kelas_siswa?success=Kelas berhasil disimpan");
    exit;
}

if (isset($_GET['hapus_kelas'])) {
    $id = (int)$_GET['hapus_kelas'];
    $cek = mysqli_query($conn, "SELECT id FROM siswa WHERE kelas_id=$id LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        $error = "Kelas tidak bisa dihapus karena masih memiliki siswa.";
    } else {
        mysqli_query($conn, "DELETE FROM kelas WHERE id=$id");
        $success = "Kelas dihapus.";
    }
    $redirect = "kelas_siswa";
    if (!empty($error)) $redirect .= "?error=" . urlencode($error);
    elseif (!empty($success)) $redirect .= "?success=" . urlencode($success);
    header("Location: $redirect");
    exit;
}

// ========== PROSES TAMBAH/EDIT/HAPUS SISWA ==========
if (isset($_POST['simpan_siswa'])) {
    $id = (int)$_POST['id'];
    $nis = trim($_POST['nis']);
    $nama = trim($_POST['nama']);
    $kelas_id = (int)$_POST['kelas_id'];
    $jk = $_POST['jenis_kelamin'];

    $error = '';
    if (empty($nis) || empty($nama) || $kelas_id <= 0 || !in_array($jk, ['L','P'])) {
        $error = "Semua field harus diisi dengan benar.";
    } else {
        $cekKelas = mysqli_query($conn, "SELECT id FROM kelas WHERE id = $kelas_id");
        if (mysqli_num_rows($cekKelas) == 0) {
            $error = "Kelas tidak valid.";
        } else {
            mysqli_begin_transaction($conn);
            try {
                if ($id == 0) { // TAMBAH BARU
                    $cekSiswa = mysqli_query($conn, "SELECT id FROM siswa WHERE nis = '$nis'");
                    if (mysqli_num_rows($cekSiswa) > 0) throw new Exception("NIS $nis sudah terdaftar.");
                    
                    $cekUser = mysqli_query($conn, "SELECT id FROM user WHERE username = '$nis'");
                    if (mysqli_num_rows($cekUser) > 0) {
                        $userData = mysqli_fetch_assoc($cekUser);
                        $user_id = $userData['id'];
                        mysqli_query($conn, "UPDATE user SET nama_lengkap = '$nama' WHERE id = $user_id");
                        $cekSiswaByUser = mysqli_query($conn, "SELECT id FROM siswa WHERE user_id = $user_id");
                        if (mysqli_num_rows($cekSiswaByUser) > 0) throw new Exception("User dengan NIS $nis sudah terhubung ke siswa lain.");
                        $insert = mysqli_query($conn, "INSERT INTO siswa (nis, nama, kelas_id, user_id, jenis_kelamin) VALUES ('$nis', '$nama', $kelas_id, $user_id, '$jk')");
                        if (!$insert) throw new Exception("Gagal insert siswa.");
                    } else {
                        $password = md5($nis);
                        $insertUser = mysqli_query($conn, "INSERT INTO user (username, password, role, nama_lengkap) VALUES ('$nis', '$password', 'siswa', '$nama')");
                        if (!$insertUser) throw new Exception("Gagal buat user.");
                        $user_id = mysqli_insert_id($conn);
                        $insertSiswa = mysqli_query($conn, "INSERT INTO siswa (nis, nama, kelas_id, user_id, jenis_kelamin) VALUES ('$nis', '$nama', $kelas_id, $user_id, '$jk')");
                        if (!$insertSiswa) {
                            mysqli_query($conn, "DELETE FROM user WHERE id = $user_id");
                            throw new Exception("Gagal insert siswa.");
                        }
                    }
                } else { // EDIT SISWA
                    $cekSiswa = mysqli_query($conn, "SELECT id FROM siswa WHERE nis='$nis' AND id != $id");
                    if (mysqli_num_rows($cekSiswa) > 0) throw new Exception("NIS $nis sudah digunakan siswa lain.");
                    $siswa_lama = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id FROM siswa WHERE id=$id"));
                    if (!$siswa_lama) throw new Exception("Data siswa tidak ditemukan.");
                    $user_id = $siswa_lama['user_id'];
                    $updateSiswa = mysqli_query($conn, "UPDATE siswa SET nis='$nis', nama='$nama', kelas_id=$kelas_id, jenis_kelamin='$jk' WHERE id=$id");
                    if (!$updateSiswa) throw new Exception("Gagal update siswa.");
                    $updateUser = mysqli_query($conn, "UPDATE user SET username='$nis', nama_lengkap='$nama' WHERE id=$user_id");
                    if (!$updateUser) throw new Exception("Gagal update user.");
                }
                mysqli_commit($conn);
                $success = "Siswa berhasil disimpan.";
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = $e->getMessage();
            }
        }
    }
    $redirect = "kelas_siswa";
    if (!empty($error)) $redirect .= "?error=" . urlencode($error);
    if (!empty($success)) $redirect .= "?success=" . urlencode($success);
    header("Location: $redirect");
    exit;
}

if (isset($_GET['hapus_siswa'])) {
    $id = (int)$_GET['hapus_siswa'];
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT user_id FROM siswa WHERE id=$id"));
    $error = '';
    $success = '';
    if ($user) {
        mysqli_begin_transaction($conn);
        try {
            mysqli_query($conn, "DELETE FROM sikap_spiritual WHERE siswa_id=$id");
            mysqli_query($conn, "DELETE FROM sikap_sosial WHERE siswa_id=$id");
            mysqli_query($conn, "DELETE FROM pengumpulan_tugas WHERE siswa_id=$id");
            mysqli_query($conn, "DELETE FROM nilai_akhir WHERE siswa_id=$id");
            mysqli_query($conn, "DELETE FROM absensi WHERE siswa_id=$id");
            mysqli_query($conn, "DELETE FROM siswa WHERE id=$id");
            mysqli_query($conn, "DELETE FROM user WHERE id=" . $user['user_id']);
            mysqli_commit($conn);
            $success = "Siswa berhasil dihapus.";
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Gagal hapus siswa: " . $e->getMessage();
        }
    } else {
        $error = "Siswa tidak ditemukan.";
    }
    $redirect = "kelas_siswa";
    if (!empty($error)) $redirect .= "?error=" . urlencode($error);
    if (!empty($success)) $redirect .= "?success=" . urlencode($success);
    header("Location: $redirect");
    exit;
}

// ========== IMPORT SISWA VIA EXCEL ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['import_siswa'])) {
    if ($_FILES['file_excel']['error'] != 0) {
        $error = "Pilih file Excel terlebih dahulu.";
    } else {
        $file_tmp = $_FILES['file_excel']['tmp_name'];
        try {
            $spreadsheet = IOFactory::load($file_tmp);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
            $success = 0;
            $errors = [];
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $nis = trim($row[0]);
                $nama = trim($row[1]);
                $kelas_id = (int)$row[2];
                $jk = isset($row[3]) ? strtoupper(trim($row[3])) : 'L';
                if (!in_array($jk, ['L','P'])) $jk = 'L';

                if (empty($nis) || empty($nama) || $kelas_id <= 0) {
                    $errors[] = "Baris " . ($i+1) . ": NIS, Nama, atau Kelas ID kosong.";
                    continue;
                }
                $cekKelas = mysqli_query($conn, "SELECT id FROM kelas WHERE id = $kelas_id");
                if (mysqli_num_rows($cekKelas) == 0) {
                    $errors[] = "Baris " . ($i+1) . ": Kelas ID $kelas_id tidak valid.";
                    continue;
                }

                mysqli_begin_transaction($conn);
                try {
                    $cekSiswa = mysqli_query($conn, "SELECT id FROM siswa WHERE nis = '$nis'");
                    if (mysqli_num_rows($cekSiswa) > 0) throw new Exception("NIS sudah terdaftar.");
                    $cekUser = mysqli_query($conn, "SELECT id FROM user WHERE username = '$nis'");
                    if (mysqli_num_rows($cekUser) > 0) {
                        $userData = mysqli_fetch_assoc($cekUser);
                        $user_id = $userData['id'];
                        mysqli_query($conn, "UPDATE user SET nama_lengkap = '$nama' WHERE id = $user_id");
                        $cekSiswaByUser = mysqli_query($conn, "SELECT id FROM siswa WHERE user_id = $user_id");
                        if (mysqli_num_rows($cekSiswaByUser) > 0) throw new Exception("User sudah terhubung ke siswa lain.");
                        $insert = mysqli_query($conn, "INSERT INTO siswa (nis, nama, kelas_id, user_id, jenis_kelamin) VALUES ('$nis', '$nama', $kelas_id, $user_id, '$jk')");
                        if (!$insert) throw new Exception("Gagal insert siswa.");
                    } else {
                        $password = md5($nis);
                        $insertUser = mysqli_query($conn, "INSERT INTO user (username, password, role, nama_lengkap) VALUES ('$nis', '$password', 'siswa', '$nama')");
                        if (!$insertUser) throw new Exception("Gagal buat user.");
                        $user_id = mysqli_insert_id($conn);
                        $insertSiswa = mysqli_query($conn, "INSERT INTO siswa (nis, nama, kelas_id, user_id, jenis_kelamin) VALUES ('$nis', '$nama', $kelas_id, $user_id, '$jk')");
                        if (!$insertSiswa) {
                            mysqli_query($conn, "DELETE FROM user WHERE id = $user_id");
                            throw new Exception("Gagal insert siswa.");
                        }
                    }
                    mysqli_commit($conn);
                    $success++;
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $errors[] = "Baris " . ($i+1) . ": " . $e->getMessage();
                }
            }
            $msg = "Import selesai! $success siswa berhasil ditambahkan.";
            if (!empty($errors)) {
                $msg .= "\\n\\nError:\\n- " . implode("\\n- ", $errors);
                $error = $msg;
            } else {
                $success_msg = $msg;
            }
        } catch (Exception $e) {
            $error = "Gagal membaca file: " . $e->getMessage();
        }
    }
    $redirect = "kelas_siswa";
    if (!empty($error)) $redirect .= "?error=" . urlencode($error);
    if (!empty($success_msg)) $redirect .= "?success=" . urlencode($success_msg);
    header("Location: $redirect");
    exit;
}

// Ambil pesan dari URL
if (isset($_GET['error']) && $_GET['error'] != '') $error_msg = htmlspecialchars($_GET['error']);
if (isset($_GET['success']) && $_GET['success'] != '') $success_msg = htmlspecialchars($_GET['success']);

$title = 'Manajemen Kelas & Siswa';
include '../includes/header.php';

// Data untuk filter kelas
$kelas_filter = isset($_GET['kelas_filter']) ? (int)$_GET['kelas_filter'] : 0;
$all_kelas = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas");

// Query siswa sesuai filter
if ($kelas_filter > 0) {
    $siswa_data = mysqli_query($conn, "SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.kelas_id = $kelas_filter ORDER BY s.nama");
} else {
    $siswa_data = mysqli_query($conn, "SELECT s.*, k.nama_kelas FROM siswa s LEFT JOIN kelas k ON s.kelas_id = k.id ORDER BY k.nama_kelas, s.nama");
}

// Data kelas untuk form
$kelas_data = mysqli_query($conn, "SELECT * FROM kelas ORDER BY tingkat, nama_kelas");
?>

<style>
/* ========== PERBAIKAN TAMPILAN ========== */
.alert {
    padding: 0.8rem 1rem;
    margin-bottom: 1.5rem;
    border-radius: 12px;
    font-weight: 500;
    border-left: 4px solid;
}
.alert-error {
    background-color: #fef2f2;
    color: #b91c1c;
    border-left-color: #dc2626;
}
.alert-success {
    background-color: #f0fdf4;
    color: #166534;
    border-left-color: #22c55e;
}
.filter-bar {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    margin-bottom: 1.5rem;
    background: #f8fafc;
    padding: 1rem;
    border-radius: 1rem;
    flex-wrap: wrap;
}
.filter-bar .form-group {
    flex: 1;
    margin-bottom: 0;
}
.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}
.btn-icon {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
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
    transform: translateY(-1px);
}
.btn-delete {
    background-color: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}
.btn-delete:hover {
    background-color: #dc2626;
    color: white;
    transform: translateY(-1px);
}
/* Tabel siswa lebih rapi */
.modern-table td, .modern-table th {
    vertical-align: middle;
}
.modern-table td:last-child {
    white-space: nowrap;
}
/* Responsif */
@media (max-width: 768px) {
    .filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .filter-bar .form-group {
        width: 100%;
    }
    .action-buttons {
        justify-content: flex-start;
    }
    .table-wrapper {
        overflow-x: auto;
    }
    .modern-table {
        min-width: 500px;
    }
}
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-users"></i> Manajemen Kelas & Siswa</h2>
    <p class="page-subtitle">Tambah, edit, hapus kelas dan siswa. Import via Excel. Filter siswa berdasarkan kelas.</p>
</div>

<?php if (!empty($error_msg)): ?>
    <div class="alert alert-error"><?= nl2br($error_msg) ?></div>
<?php endif; ?>
<?php if (!empty($success_msg)): ?>
    <div class="alert alert-success"><?= nl2br($success_msg) ?></div>
<?php endif; ?>

<!-- ========== FORM KELAS ========== -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-school"></i> Kelola Kelas</div>
    <form method="POST" class="form-row">
        <input type="hidden" name="id" id="kelas_id" value="0">
        <div class="form-group">
            <label>Nama Kelas</label>
            <input type="text" name="nama_kelas" id="kelas_nama" class="form-input" required>
        </div>
        <div class="form-group">
            <label>Tingkat</label>
            <select name="tingkat" id="kelas_tingkat" class="form-select">
                <option value="VII">VII</option>
                <option value="VIII">VIII</option>
                <option value="IX">IX</option>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" name="simpan_kelas" class="btn btn-primary">Simpan Kelas</button>
            <button type="button" id="batal_kelas" class="btn btn-outline">Batal</button>
        </div>
    </form>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr><th>ID</th><th>Nama Kelas</th><th>Tingkat</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php while($k = mysqli_fetch_assoc($kelas_data)): ?>
                <tr>
                    <td><?= $k['id'] ?></td>
                    <td><strong><?= htmlspecialchars($k['nama_kelas']) ?></strong></td>
                    <td><?= $k['tingkat'] ?></td>
                    <td class="action-buttons">
                        <button class="btn-icon btn-edit btn-edit-kelas" data-id="<?= $k['id'] ?>" data-nama="<?= htmlspecialchars($k['nama_kelas']) ?>" data-tingkat="<?= $k['tingkat'] ?>"><i class="fas fa-edit"></i> Edit</button>
                        <a href="?hapus_kelas=<?= $k['id'] ?>" class="btn-icon btn-delete" onclick="return confirm('Yakin hapus kelas?')"><i class="fas fa-trash-alt"></i> Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ========== FORM SISWA ========== -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-user-graduate"></i> Kelola Siswa</div>
    
    <!-- Filter Kelas -->
    <div class="filter-bar">
        <div class="form-group">
            <label>Filter Siswa Berdasarkan Kelas</label>
            <select id="filter_kelas" class="form-select" onchange="window.location.href='?kelas_filter='+this.value">
                <option value="0">-- Semua Kelas --</option>
                <?php 
                $all_kelas_filter = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas");
                while($k = mysqli_fetch_assoc($all_kelas_filter)): ?>
                    <option value="<?= $k['id'] ?>" <?= ($kelas_filter == $k['id']) ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>&nbsp;</label>
            <button type="button" class="btn btn-outline btn-sm" onclick="window.location.href='kelas_siswa'">Reset Filter</button>
        </div>
    </div>

    <!-- Form Tambah/Edit Siswa -->
    <form method="POST" class="form-row" id="form_siswa">
        <input type="hidden" name="id" id="siswa_id" value="0">
        <div class="form-group">
            <label>NIS</label>
            <input type="text" name="nis" id="siswa_nis" class="form-input" required>
        </div>
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" id="siswa_nama" class="form-input" required>
        </div>
        <div class="form-group">
            <label>Kelas</label>
            <select name="kelas_id" id="siswa_kelas" class="form-select" required>
                <option value="">-- Pilih Kelas --</option>
                <?php 
                $all_kelas_form = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas");
                while($k = mysqli_fetch_assoc($all_kelas_form)): ?>
                    <option value="<?= $k['id'] ?>"><?= $k['nama_kelas'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Jenis Kelamin</label>
            <select name="jenis_kelamin" id="siswa_jk" class="form-select">
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" name="simpan_siswa" class="btn btn-primary">Simpan Siswa</button>
            <button type="button" id="batal_siswa" class="btn btn-outline">Batal</button>
        </div>
    </form>

    <!-- Import Excel -->
    <hr style="margin: 1.5rem 0;">
    <div class="form-title"><i class="fas fa-upload"></i> Import Siswa (Excel)</div>
    <form method="POST" enctype="multipart/form-data" class="form-row" action="">
        <div class="form-group">
            <label>File Excel (.xlsx)</label>
            <input type="file" name="file_excel" class="form-input" accept=".xlsx" required>
        </div>
        <div class="form-group">
            <button type="submit" name="import_siswa" class="btn btn-primary"><i class="fas fa-upload"></i> Import</button>
            <a href="download_template_siswa" class="btn btn-outline"><i class="fas fa-download"></i> Download Template</a>
        </div>
    </form>

    <div class="table-wrapper" style="margin-top: 1.5rem;">
        <table class="modern-table">
            <thead>
                <tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Jenis Kelamin</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($siswa_data) == 0): ?>
                    <tr><td colspan="5" style="text-align: center;">Belum ada data siswa. </td></tr>
                <?php else: ?>
                    <?php while($s = mysqli_fetch_assoc($siswa_data)): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['nis']) ?></td>
                        <td><strong><?= htmlspecialchars($s['nama']) ?></strong></td>
                        <td><?= htmlspecialchars($s['nama_kelas']) ?></td>
                        <td><?= $s['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                        <td class="action-buttons">
                            <button class="btn-icon btn-edit btn-edit-siswa" data-id="<?= $s['id'] ?>" data-nis="<?= $s['nis'] ?>" data-nama="<?= htmlspecialchars($s['nama']) ?>" data-kelas="<?= $s['kelas_id'] ?>" data-jk="<?= $s['jenis_kelamin'] ?>"><i class="fas fa-edit"></i> Edit</button>
                            <a href="?hapus_siswa=<?= $s['id'] ?>&kelas_filter=<?= $kelas_filter ?>" class="btn-icon btn-delete" onclick="return confirm('Yakin hapus siswa? Semua data terkait akan hilang.')"><i class="fas fa-trash-alt"></i> Hapus</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Edit Kelas
document.querySelectorAll('.btn-edit-kelas').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('kelas_id').value = this.dataset.id;
        document.getElementById('kelas_nama').value = this.dataset.nama;
        document.getElementById('kelas_tingkat').value = this.dataset.tingkat;
    });
});
document.getElementById('batal_kelas')?.addEventListener('click', function() {
    document.getElementById('kelas_id').value = 0;
    document.getElementById('kelas_nama').value = '';
    document.getElementById('kelas_tingkat').value = 'VII';
});

// Edit Siswa
document.querySelectorAll('.btn-edit-siswa').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('siswa_id').value = this.dataset.id;
        document.getElementById('siswa_nis').value = this.dataset.nis;
        document.getElementById('siswa_nama').value = this.dataset.nama;
        document.getElementById('siswa_kelas').value = this.dataset.kelas;
        document.getElementById('siswa_jk').value = this.dataset.jk;
    });
});
document.getElementById('batal_siswa')?.addEventListener('click', function() {
    document.getElementById('siswa_id').value = 0;
    document.getElementById('siswa_nis').value = '';
    document.getElementById('siswa_nama').value = '';
    document.getElementById('siswa_kelas').value = '';
    document.getElementById('siswa_jk').value = 'L';
});
</script>

<?php
include '../includes/footer.php';
ob_end_flush();
?>