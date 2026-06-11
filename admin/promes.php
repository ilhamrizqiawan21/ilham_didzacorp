<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Program Semester (PROMES)';
include '../includes/header.php';

// Ambil daftar bab untuk dropdown
$bab_list = mysqli_query($conn, "SELECT id, judul_bab FROM bab ORDER BY id");

// Tambah PROMES
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $bab_id = (int)$_POST['bab_id'];
    $bulan = mysqli_real_escape_string($conn, $_POST['bulan']);
    $minggu_ke = (int)$_POST['minggu_ke'];
    $topik = mysqli_real_escape_string($conn, $_POST['topik_materi']);
    $alokasi = mysqli_real_escape_string($conn, $_POST['alokasi_waktu']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $tahun = mysqli_real_escape_string($conn, $_POST['tahun_pelajaran']);
    mysqli_query($conn, "INSERT INTO promes (bab_id, bulan, minggu_ke, topik_materi, alokasi_waktu, semester, tahun_pelajaran) 
                         VALUES ($bab_id, '$bulan', $minggu_ke, '$topik', '$alokasi', '$semester', '$tahun')");
    echo "<script>alert('PROMES ditambahkan'); window.location.href='promes';</script>";
}

// Edit PROMES
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $bab_id = (int)$_POST['bab_id'];
    $bulan = mysqli_real_escape_string($conn, $_POST['bulan']);
    $minggu_ke = (int)$_POST['minggu_ke'];
    $topik = mysqli_real_escape_string($conn, $_POST['topik_materi']);
    $alokasi = mysqli_real_escape_string($conn, $_POST['alokasi_waktu']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $tahun = mysqli_real_escape_string($conn, $_POST['tahun_pelajaran']);
    mysqli_query($conn, "UPDATE promes SET bab_id=$bab_id, bulan='$bulan', minggu_ke=$minggu_ke, topik_materi='$topik', 
                          alokasi_waktu='$alokasi', semester='$semester', tahun_pelajaran='$tahun' WHERE id=$id");
    echo "<script>alert('PROMES diupdate'); window.location.href='promes';</script>";
}

// Hapus PROMES
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM promes WHERE id=$id");
    echo "<script>alert('PROMES dihapus'); window.location.href='promes';</script>";
}

// Filter semester
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '1';
$data = mysqli_query($conn, "SELECT p.*, b.judul_bab 
    FROM promes p 
    LEFT JOIN bab b ON p.bab_id = b.id 
    WHERE p.semester='$semester_filter' 
    ORDER BY FIELD(p.bulan,'Juli','Agustus','September','Oktober','November','Desember','Januari','Februari','Maret','April','Mei','Juni'), p.minggu_ke");
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-calendar-week"></i> Program Semester (PROMES)</h2>
    <p class="page-subtitle">Input jadwal per bulan dan minggu, terintegrasi dengan bab.</p>
</div>

<!-- Tombol Export -->
<div class="btn-group" style="margin-bottom: 1rem;">
    <a href="export_promes_excel?semester=<?= $semester_filter ?>" class="btn btn-outline"><i class="fas fa-file-excel"></i> Export Excel</a>
    <a href="export_promes_html?semester=<?= $semester_filter ?>" class="btn btn-outline" target="_blank"><i class="fas fa-print"></i> Cetak</a>
</div>

<!-- Form Tambah PROMES -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-plus-circle"></i> Tambah Baris PROMES</div>
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Bab</label>
                <select name="bab_id" class="form-select" required>
                    <option value="">-- Pilih Bab --</option>
                    <?php while($b = mysqli_fetch_assoc($bab_list)): ?>
                        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['judul_bab']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Semester</label>
                <select name="semester" class="form-select">
                    <option value="1">Semester 1 (Ganjil)</option>
                    <option value="2">Semester 2 (Genap)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Tahun Pelajaran</label>
                <input type="text" name="tahun_pelajaran" class="form-input" value="2025/2026">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Bulan</label>
                <select name="bulan" class="form-select">
                    <option value="Juli">Juli</option><option value="Agustus">Agustus</option><option value="September">September</option>
                    <option value="Oktober">Oktober</option><option value="November">November</option><option value="Desember">Desember</option>
                    <option value="Januari">Januari</option><option value="Februari">Februari</option><option value="Maret">Maret</option>
                    <option value="April">April</option><option value="Mei">Mei</option><option value="Juni">Juni</option>
                </select>
            </div>
            <div class="form-group">
                <label>Minggu ke-</label>
                <input type="number" name="minggu_ke" class="form-input" min="1" max="5" required>
            </div>
        </div>
        <div class="form-group">
            <label>Topik / Materi</label>
            <input type="text" name="topik_materi" class="form-input" required>
        </div>
        <div class="form-group">
            <label>Alokasi Waktu</label>
            <input type="text" name="alokasi_waktu" class="form-input" placeholder="2 JP">
        </div>
        <button type="submit" name="tambah" class="btn btn-primary"><i class="fas fa-save"></i> Simpan PROMES</button>
    </form>
</div>

<!-- Filter Semester -->
<div class="btn-group" style="margin-bottom: 1rem;">
    <a href="?semester=1" class="btn <?= $semester_filter=='1' ? 'btn-primary' : 'btn-outline' ?>"><i class="fas fa-book-open"></i> Semester 1</a>
    <a href="?semester=2" class="btn <?= $semester_filter=='2' ? 'btn-primary' : 'btn-outline' ?>"><i class="fas fa-book"></i> Semester 2</a>
</div>

<!-- Daftar PROMES -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-list"></i> Data PROMES - Semester <?= $semester_filter ?></div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Bab</th>
                    <th>Bulan</th>
                    <th>Minggu</th>
                    <th>Topik/Materi</th>
                    <th>Alokasi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['judul_bab']) ?></strong></td>
                    <td><?= $row['bulan'] ?></td>
                    <td><?= $row['minggu_ke'] ?></td>
                    <td><?= htmlspecialchars($row['topik_materi']) ?></td>
                    <td><?= $row['alokasi_waktu'] ?></td>
                    <td>
                        <button class="btn btn-sm" onclick="editPromes(<?= $row['id'] ?>, <?= $row['bab_id'] ?>, '<?= addslashes($row['bulan']) ?>', <?= $row['minggu_ke'] ?>, '<?= addslashes($row['topik_materi']) ?>', '<?= $row['alokasi_waktu'] ?>', '<?= $row['semester'] ?>', '<?= $row['tahun_pelajaran'] ?>')"><i class="fas fa-edit"></i> Edit</button>
                        <a href="?hapus=<?= $row['id'] ?>&semester=<?= $semester_filter ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus data ini?')"><i class="fas fa-trash"></i> Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit PROMES -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit PROMES</h3>
            <button class="modal-close" onclick="tutupModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Bab</label>
                    <select name="bab_id" id="edit_bab" class="form-select">
                        <?php 
                        $bab_list2 = mysqli_query($conn, "SELECT id, judul_bab FROM bab ORDER BY id");
                        while($b = mysqli_fetch_assoc($bab_list2)): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['judul_bab']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Bulan</label>
                    <select name="bulan" id="edit_bulan" class="form-select">
                        <option value="Juli">Juli</option><option value="Agustus">Agustus</option><option value="September">September</option>
                        <option value="Oktober">Oktober</option><option value="November">November</option><option value="Desember">Desember</option>
                        <option value="Januari">Januari</option><option value="Februari">Februari</option><option value="Maret">Maret</option>
                        <option value="April">April</option><option value="Mei">Mei</option><option value="Juni">Juni</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Minggu ke-</label>
                    <input type="number" name="minggu_ke" id="edit_minggu" class="form-input" min="1" max="5">
                </div>
                <div class="form-group">
                    <label>Topik / Materi</label>
                    <input type="text" name="topik_materi" id="edit_topik" class="form-input">
                </div>
                <div class="form-group">
                    <label>Alokasi Waktu</label>
                    <input type="text" name="alokasi_waktu" id="edit_waktu" class="form-input">
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester" id="edit_semester" class="form-select">
                        <option value="1">Semester 1 (Ganjil)</option>
                        <option value="2">Semester 2 (Genap)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tahun Pelajaran</label>
                    <input type="text" name="tahun_pelajaran" id="edit_tahun" class="form-input">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="edit" class="btn btn-primary">Update</button>
                <button type="button" class="btn" onclick="tutupModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function editPromes(id, bab_id, bulan, minggu, topik, waktu, semester, tahun) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_bab').value = bab_id;
    document.getElementById('edit_bulan').value = bulan;
    document.getElementById('edit_minggu').value = minggu;
    document.getElementById('edit_topik').value = topik;
    document.getElementById('edit_waktu').value = waktu;
    document.getElementById('edit_semester').value = semester;
    document.getElementById('edit_tahun').value = tahun;
    document.getElementById('editModal').style.display = 'flex';
}
function tutupModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>