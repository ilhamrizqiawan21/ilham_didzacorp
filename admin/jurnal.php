<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Jurnal Mengajar';
include '../includes/header.php';

// Ambil daftar bab untuk dropdown
$bab_list = mysqli_query($conn, "SELECT id, judul_bab FROM bab ORDER BY id");

// Tambah jurnal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $bab_id = (int)$_POST['bab_id'];
    $alur_tujuan = mysqli_real_escape_string($conn, $_POST['alur_tujuan']);
    $materi = mysqli_real_escape_string($conn, $_POST['materi']);
    $hari_tanggal = mysqli_real_escape_string($conn, $_POST['hari_tanggal']);
    $jam_ke = mysqli_real_escape_string($conn, $_POST['jam_ke']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    mysqli_query($conn, "INSERT INTO jurnal (bab_id, alur_tujuan, materi, hari_tanggal, jam_ke, keterangan) 
                         VALUES ($bab_id, '$alur_tujuan', '$materi', '$hari_tanggal', '$jam_ke', '$keterangan')");
    echo "<script>alert('Jurnal ditambahkan'); window.location.href='jurnal';</script>";
}

// Edit jurnal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $bab_id = (int)$_POST['bab_id'];
    $alur_tujuan = mysqli_real_escape_string($conn, $_POST['alur_tujuan']);
    $materi = mysqli_real_escape_string($conn, $_POST['materi']);
    $hari_tanggal = mysqli_real_escape_string($conn, $_POST['hari_tanggal']);
    $jam_ke = mysqli_real_escape_string($conn, $_POST['jam_ke']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    mysqli_query($conn, "UPDATE jurnal SET bab_id=$bab_id, alur_tujuan='$alur_tujuan', materi='$materi', 
                          hari_tanggal='$hari_tanggal', jam_ke='$jam_ke', keterangan='$keterangan' WHERE id=$id");
    echo "<script>alert('Jurnal diupdate'); window.location.href='jurnal';</script>";
}

// Hapus jurnal
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM jurnal WHERE id=$id");
    echo "<script>alert('Jurnal dihapus'); window.location.href='jurnal';</script>";
}

// Filter bulan/tahun
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('Y-m');
$tahun_bulan = explode('-', $bulan);
$tahun = $tahun_bulan[0];
$bulan_angka = $tahun_bulan[1];
$where = "DATE_FORMAT(j.hari_tanggal, '%Y-%m') = '$bulan'";
$data = mysqli_query($conn, "SELECT j.*, b.judul_bab FROM jurnal j LEFT JOIN bab b ON j.bab_id = b.id WHERE $where ORDER BY j.hari_tanggal DESC");
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-journal-whills"></i> Jurnal Mengajar Guru</h2>
    <p class="page-subtitle">Catatan harian kegiatan pembelajaran (terintegrasi dengan bab)</p>
</div>

<!-- Filter Bulan dan Tombol Export -->
<div class="form-container" style="margin-bottom:1rem;">
    <form method="GET" class="form-row">
        <div class="form-group">
            <label>Pilih Bulan</label>
            <select name="bulan" class="form-select" onchange="this.form.submit()">
                <?= bulan_options($bulan) ?>
            </select>
        </div>
        <div class="form-group">
            <label>&nbsp;</label>
            <div>
                <a href="export_jurnal_excel?bulan=<?= $bulan ?>" class="btn btn-outline"><i class="fas fa-file-excel"></i> Export Excel</a>
                <a href="export_jurnal_html?bulan=<?= $bulan ?>" class="btn btn-outline" target="_blank"><i class="fas fa-print"></i> Cetak</a>
            </div>
        </div>
    </form>
</div>

<!-- Form Tambah Jurnal -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-plus-circle"></i> Catatan Jurnal Baru</div>
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
                <label>Hari/Tanggal</label>
                <input type="date" name="hari_tanggal" class="form-input" required>
            </div>
        </div>
        <div class="form-group">
            <label>Alur Tujuan Pembelajaran</label>
            <textarea name="alur_tujuan" class="form-textarea" rows="2" required></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Materi Pokok</label>
                <input type="text" name="materi" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Jam ke-</label>
                <input type="text" name="jam_ke" class="form-input" placeholder="1,2,3">
            </div>
        </div>
        <div class="form-group">
            <label>Keterangan / Catatan</label>
            <textarea name="keterangan" class="form-textarea" rows="2" placeholder="Siswa hadir, kendala, dll"></textarea>
        </div>
        <button type="submit" name="tambah" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Jurnal</button>
    </form>
</div>

<!-- Daftar Jurnal -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-list"></i> Riwayat Jurnal Mengajar - Bulan <?= date('F Y', strtotime($bulan)) ?></div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr><th>Tanggal</th><th>Bab</th><th>Alur Tujuan</th><th>Materi</th><th>Jam</th><th>Keterangan</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= tgl_indonesia($row['hari_tanggal']) ?></td>
                    <td><strong><?= htmlspecialchars($row['judul_bab']) ?></strong></td>
                    <td><?= nl2br(htmlspecialchars($row['alur_tujuan'])) ?></td>
                    <td><?= htmlspecialchars($row['materi']) ?></td>
                    <td><?= $row['jam_ke'] ?></td>
                    <td><?= nl2br(htmlspecialchars($row['keterangan'])) ?></td>
                    <td>
                        <button class="btn btn-sm" onclick="editJurnal(<?= $row['id'] ?>, <?= $row['bab_id'] ?>, '<?= addslashes($row['alur_tujuan']) ?>', '<?= addslashes($row['materi']) ?>', '<?= $row['hari_tanggal'] ?>', '<?= $row['jam_ke'] ?>', '<?= addslashes($row['keterangan']) ?>')"><i class="fas fa-edit"></i> Edit</button>
                        <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus jurnal ini?')"><i class="fas fa-trash"></i> Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit Jurnal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Jurnal</h3>
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
                <div class="form-group"><label>Alur Tujuan</label><textarea name="alur_tujuan" id="edit_alur" class="form-textarea" rows="2"></textarea></div>
                <div class="form-group"><label>Materi</label><input type="text" name="materi" id="edit_materi" class="form-input"></div>
                <div class="form-group"><label>Hari/Tanggal</label><input type="date" name="hari_tanggal" id="edit_tanggal" class="form-input"></div>
                <div class="form-group"><label>Jam ke-</label><input type="text" name="jam_ke" id="edit_jam" class="form-input"></div>
                <div class="form-group"><label>Keterangan</label><textarea name="keterangan" id="edit_keterangan" class="form-textarea" rows="2"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="edit" class="btn btn-primary">Update</button>
                <button type="button" class="btn" onclick="tutupModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function editJurnal(id, bab_id, alur, materi, tanggal, jam, ket) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_bab').value = bab_id;
    document.getElementById('edit_alur').value = alur;
    document.getElementById('edit_materi').value = materi;
    document.getElementById('edit_tanggal').value = tanggal;
    document.getElementById('edit_jam').value = jam;
    document.getElementById('edit_keterangan').value = ket;
    document.getElementById('editModal').style.display = 'flex';
}
function tutupModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>