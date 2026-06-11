<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Kelola LKPD';
include '../includes/header.php';

if (!file_exists('../uploads/lkpd')) mkdir('../uploads/lkpd', 0777, true);

// Ambil daftar bab untuk dropdown
$bab_list = mysqli_query($conn, "SELECT id, judul_bab FROM bab ORDER BY id");

// Tambah LKPD
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $bab_id = (int)$_POST['bab_id'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $petunjuk = mysqli_real_escape_string($conn, $_POST['petunjuk']);
    $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan']);
    $materi = mysqli_real_escape_string($conn, $_POST['materi']);
    $tugas = mysqli_real_escape_string($conn, $_POST['tugas']);
    $rubrik = mysqli_real_escape_string($conn, $_POST['rubrik']);
    
    $file_name = null;
    if ($_FILES['lampiran']['error'] == 0) {
        $ext = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['lampiran']['tmp_name'], '../uploads/lkpd/' . $file_name);
    }
    
    mysqli_query($conn, "INSERT INTO lkpd (bab_id, judul, petunjuk_belajar, tujuan_pembelajaran, materi_pokok, tugas, rubrik, file_lampiran) 
                         VALUES ($bab_id, '$judul', '$petunjuk', '$tujuan', '$materi', '$tugas', '$rubrik', '$file_name')");
    echo "<script>alert('LKPD berhasil ditambahkan'); window.location.href='lkpd';</script>";
}

// Edit LKPD
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $bab_id = (int)$_POST['bab_id'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $petunjuk = mysqli_real_escape_string($conn, $_POST['petunjuk']);
    $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan']);
    $materi = mysqli_real_escape_string($conn, $_POST['materi']);
    $tugas = mysqli_real_escape_string($conn, $_POST['tugas']);
    $rubrik = mysqli_real_escape_string($conn, $_POST['rubrik']);
    
    if ($_FILES['lampiran']['error'] == 0) {
        $q = "SELECT file_lampiran FROM lkpd WHERE id=$id";
        $old = mysqli_fetch_assoc(mysqli_query($conn, $q));
        if ($old['file_lampiran'] && file_exists('../uploads/lkpd/'.$old['file_lampiran'])) unlink('../uploads/lkpd/'.$old['file_lampiran']);
        $ext = pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['lampiran']['tmp_name'], '../uploads/lkpd/' . $file_name);
        mysqli_query($conn, "UPDATE lkpd SET bab_id=$bab_id, judul='$judul', petunjuk_belajar='$petunjuk', tujuan_pembelajaran='$tujuan', 
                              materi_pokok='$materi', tugas='$tugas', rubrik='$rubrik', file_lampiran='$file_name' WHERE id=$id");
    } else {
        mysqli_query($conn, "UPDATE lkpd SET bab_id=$bab_id, judul='$judul', petunjuk_belajar='$petunjuk', tujuan_pembelajaran='$tujuan', 
                              materi_pokok='$materi', tugas='$tugas', rubrik='$rubrik' WHERE id=$id");
    }
    echo "<script>alert('LKPD diupdate'); window.location.href='lkpd';</script>";
}

// Hapus LKPD
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT file_lampiran FROM lkpd WHERE id=$id"));
    if ($row['file_lampiran'] && file_exists('../uploads/lkpd/'.$row['file_lampiran'])) unlink('../uploads/lkpd/'.$row['file_lampiran']);
    mysqli_query($conn, "DELETE FROM lkpd WHERE id=$id");
    echo "<script>alert('LKPD dihapus'); window.location.href='lkpd';</script>";
}

$data = mysqli_query($conn, "SELECT l.*, b.judul_bab FROM lkpd l LEFT JOIN bab b ON l.bab_id = b.id ORDER BY l.id DESC");
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-pen-alt"></i> Lembar Kerja Peserta Didik (LKPD)</h2>
    <p class="page-subtitle">Input LKPD, terintegrasi dengan bab.</p>
</div>

<!-- Tombol Export -->
<div class="btn-group" style="margin-bottom:1rem;">
    <a href="export_lkpd_excel" class="btn btn-outline"><i class="fas fa-file-excel"></i> Export Excel</a>
    <a href="export_lkpd_html" class="btn btn-outline" target="_blank"><i class="fas fa-print"></i> Cetak</a>
</div>

<!-- Form Tambah LKPD -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-plus-circle"></i> Tambah LKPD Baru</div>
    <form method="POST" enctype="multipart/form-data">
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
                <label>Judul LKPD</label>
                <input type="text" name="judul" class="form-input" required placeholder="Contoh: LKPD Mad 'Iwad">
            </div>
        </div>
        <div class="form-group">
            <label>Petunjuk Belajar</label>
            <textarea name="petunjuk" class="form-textarea" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label>Tujuan Pembelajaran</label>
            <textarea name="tujuan" class="form-textarea" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label>Materi Pokok</label>
            <textarea name="materi" class="form-textarea" rows="3"></textarea>
        </div>
        <div class="form-group">
            <label>Tugas / Langkah Kerja</label>
            <textarea name="tugas" class="form-textarea" rows="4"></textarea>
        </div>
        <div class="form-group">
            <label>Rubrik Penilaian</label>
            <textarea name="rubrik" class="form-textarea" rows="4"></textarea>
        </div>
        <div class="form-group">
            <label>File Lampiran (PDF/DOC/DOCX/JPG/PNG)</label>
            <input type="file" name="lampiran" class="form-input">
        </div>
        <button type="submit" name="tambah" class="btn btn-primary"><i class="fas fa-save"></i> Simpan LKPD</button>
    </form>
</div>

<!-- Daftar LKPD -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-list"></i> Daftar LKPD</div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Bab</th>
                    <th>Judul</th>
                    <th>File</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['judul_bab']) ?></strong></td>
                    <td><?= htmlspecialchars($row['judul']) ?></td>
                    <td>
                        <?php if($row['file_lampiran']): ?>
                            <a href="../uploads/lkpd/<?= $row['file_lampiran'] ?>" target="_blank" class="btn btn-sm"><i class="fas fa-download"></i> Download</a>
                        <?php else: ?> - <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm" onclick="editLKPD(<?= $row['id'] ?>, <?= $row['bab_id'] ?>, '<?= addslashes($row['judul']) ?>', '<?= addslashes($row['petunjuk_belajar']) ?>', '<?= addslashes($row['tujuan_pembelajaran']) ?>', '<?= addslashes($row['materi_pokok']) ?>', '<?= addslashes($row['tugas']) ?>', '<?= addslashes($row['rubrik']) ?>')"><i class="fas fa-edit"></i> Edit</button>
                        <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus LKPD ini?')"><i class="fas fa-trash"></i> Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit LKPD -->
<div id="editModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit LKPD</h3>
            <button class="modal-close" onclick="tutupModal()">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
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
                    <label>Judul LKPD</label>
                    <input type="text" name="judul" id="edit_judul" class="form-input">
                </div>
                <div class="form-group">
                    <label>Petunjuk Belajar</label>
                    <textarea name="petunjuk" id="edit_petunjuk" class="form-textarea" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Tujuan Pembelajaran</label>
                    <textarea name="tujuan" id="edit_tujuan" class="form-textarea" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Materi Pokok</label>
                    <textarea name="materi" id="edit_materi" class="form-textarea" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Tugas</label>
                    <textarea name="tugas" id="edit_tugas" class="form-textarea" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Rubrik</label>
                    <textarea name="rubrik" id="edit_rubrik" class="form-textarea" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Ganti File Lampiran (opsional)</label>
                    <input type="file" name="lampiran" class="form-input">
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
function editLKPD(id, bab_id, judul, petunjuk, tujuan, materi, tugas, rubrik) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_bab').value = bab_id;
    document.getElementById('edit_judul').value = judul;
    document.getElementById('edit_petunjuk').value = petunjuk;
    document.getElementById('edit_tujuan').value = tujuan;
    document.getElementById('edit_materi').value = materi;
    document.getElementById('edit_tugas').value = tugas;
    document.getElementById('edit_rubrik').value = rubrik;
    document.getElementById('editModal').style.display = 'flex';
}
function tutupModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>