<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Kelola ATP';
include '../includes/header.php';

// Ambil daftar bab untuk dropdown (dengan alokasi waktu dan materi pokok)
$bab_list = mysqli_query($conn, "SELECT id, judul_bab, alokasi_waktu, materi_pokok FROM bab ORDER BY id");

// Tambah ATP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $bab_id = (int)$_POST['bab_id'];
    $alur_tujuan = mysqli_real_escape_string($conn, $_POST['alur_tujuan']);
    $materi = mysqli_real_escape_string($conn, $_POST['materi']);
    $alokasi_waktu = mysqli_real_escape_string($conn, $_POST['alokasi_waktu']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    mysqli_query($conn, "INSERT INTO atp (bab_id, alur_tujuan, materi, alokasi_waktu, semester) 
                         VALUES ($bab_id, '$alur_tujuan', '$materi', '$alokasi_waktu', '$semester')");
    echo "<script>alert('ATP berhasil ditambahkan'); window.location.href='atp';</script>";
}

// Edit ATP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $bab_id = (int)$_POST['bab_id'];
    $alur_tujuan = mysqli_real_escape_string($conn, $_POST['alur_tujuan']);
    $materi = mysqli_real_escape_string($conn, $_POST['materi']);
    $alokasi_waktu = mysqli_real_escape_string($conn, $_POST['alokasi_waktu']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    mysqli_query($conn, "UPDATE atp SET bab_id=$bab_id, alur_tujuan='$alur_tujuan', materi='$materi', 
                          alokasi_waktu='$alokasi_waktu', semester='$semester' WHERE id=$id");
    echo "<script>alert('ATP diupdate'); window.location.href='atp';</script>";
}

// Hapus ATP
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM atp WHERE id=$id");
    echo "<script>alert('ATP dihapus'); window.location.href='atp';</script>";
}

// Filter semester
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';
$where = $semester_filter ? "WHERE a.semester='$semester_filter'" : "";
$data = mysqli_query($conn, "SELECT a.*, b.judul_bab FROM atp a LEFT JOIN bab b ON a.bab_id = b.id $where ORDER BY a.semester, a.id");
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-list-check"></i> Alur Tujuan Pembelajaran (ATP)</h2>
    <p class="page-subtitle">Data terintegrasi dengan bab. Alokasi waktu dan materi pokok otomatis mengikuti bab yang dipilih (dapat diedit manual).</p>
</div>

<!-- Filter Semester dan Tombol Export -->
<div class="form-container no-print" style="margin-bottom:1rem;">
    <form method="GET" class="form-row">
        <div class="form-group">
            <label>Filter Semester</label>
            <select name="semester" class="form-select" onchange="this.form.submit()">
                <option value="">Semua Semester</option>
                <option value="1" <?= $semester_filter == '1' ? 'selected' : '' ?>>Semester 1 (Ganjil)</option>
                <option value="2" <?= $semester_filter == '2' ? 'selected' : '' ?>>Semester 2 (Genap)</option>
            </select>
        </div>
        <div class="form-group">
            <label>&nbsp;</label>
            <div>
                <a href="export_atp_excel?semester=<?= $semester_filter ?>" class="btn btn-outline"><i class="fas fa-file-excel"></i> Export Excel</a>
                <a href="export_atp_html?semester=<?= $semester_filter ?>" class="btn btn-outline" target="_blank"><i class="fas fa-print"></i> Cetak</a>
            </div>
        </div>
    </form>
</div>

<!-- Form Tambah ATP -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-plus-circle"></i> Tambah Baris ATP</div>
    <form method="POST" id="formTambahATP">
        <div class="form-row">
            <div class="form-group">
                <label>Bab</label>
                <select name="bab_id" id="bab_id_tambah" class="form-select" required>
                    <option value="">-- Pilih Bab --</option>
                    <?php 
                    $bab_query = mysqli_query($conn, "SELECT id, judul_bab, alokasi_waktu, materi_pokok FROM bab ORDER BY id");
                    while($b = mysqli_fetch_assoc($bab_query)): ?>
                        <option value="<?= $b['id'] ?>" data-alokasi="<?= htmlspecialchars($b['alokasi_waktu']) ?>" data-materi="<?= htmlspecialchars($b['materi_pokok']) ?>">
                            <?= htmlspecialchars($b['judul_bab']) ?>
                        </option>
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
        </div>
        <div class="form-group">
            <label>Alur Tujuan Pembelajaran</label>
            <textarea name="alur_tujuan" class="form-textarea" rows="3" required></textarea>
        </div>
        <div class="form-group">
            <label>Materi Pokok</label>
            <input type="text" name="materi" id="materi_tambah" class="form-input" required placeholder="Akan terisi otomatis dari bab">
        </div>
        <div class="form-group">
            <label>Alokasi Waktu (JP)</label>
            <input type="text" name="alokasi_waktu" id="alokasi_waktu_tambah" class="form-input" readonly style="background:#f3f4f6;" placeholder="Pilih bab terlebih dahulu">
        </div>
        <button type="submit" name="tambah" class="btn btn-primary">Simpan ATP</button>
    </form>
</div>

<!-- Daftar ATP -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-list"></i> Data ATP</div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Bab</th>
                    <th>Alur Tujuan</th>
                    <th>Materi</th>
                    <th>Waktu</th>
                    <th>Semester</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['judul_bab']) ?></strong></td>
                    <td><?= nl2br(htmlspecialchars($row['alur_tujuan'])) ?></td>
                    <td><?= htmlspecialchars($row['materi']) ?></td>
                    <td><?= $row['alokasi_waktu'] ?></td>
                    <td><?= $row['semester'] == 1 ? 'Ganjil' : 'Genap' ?></td>
                    <td>
                        <button class="btn btn-sm btn-edit-atp" data-id="<?= $row['id'] ?>" data-bab="<?= $row['bab_id'] ?>" data-alur="<?= htmlspecialchars($row['alur_tujuan']) ?>" data-materi="<?= htmlspecialchars($row['materi']) ?>" data-waktu="<?= $row['alokasi_waktu'] ?>" data-semester="<?= $row['semester'] ?>"><i class="fas fa-edit"></i> Edit</button>
                        <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i> Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit ATP -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Edit ATP</h3><button class="modal-close" onclick="tutupModal()">&times;</button></div>
        <form method="POST" id="formEditATP">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Bab</label>
                    <select name="bab_id" id="edit_bab" class="form-select" required>
                        <?php 
                        $bab_query_edit = mysqli_query($conn, "SELECT id, judul_bab, alokasi_waktu, materi_pokok FROM bab ORDER BY id");
                        while($b = mysqli_fetch_assoc($bab_query_edit)): ?>
                            <option value="<?= $b['id'] ?>" data-alokasi="<?= htmlspecialchars($b['alokasi_waktu']) ?>" data-materi="<?= htmlspecialchars($b['materi_pokok']) ?>">
                                <?= htmlspecialchars($b['judul_bab']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Alur Tujuan</label>
                    <textarea name="alur_tujuan" id="edit_alur" class="form-textarea" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Materi Pokok</label>
                    <input type="text" name="materi" id="edit_materi" class="form-input">
                </div>
                <div class="form-group">
                    <label>Alokasi Waktu (JP)</label>
                    <input type="text" name="alokasi_waktu" id="edit_waktu" class="form-input" readonly style="background:#f3f4f6;">
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
                <button type="submit" name="edit" class="btn btn-primary">Update</button>
                <button type="button" class="btn" onclick="tutupModal()">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ========== FORM TAMBAH ==========
    const babSelectTambah = document.getElementById('bab_id_tambah');
    const materiTambah = document.getElementById('materi_tambah');
    const waktuTambah = document.getElementById('alokasi_waktu_tambah');

    function updateFromBabTambah() {
        let selected = babSelectTambah.options[babSelectTambah.selectedIndex];
        let alokasi = selected.getAttribute('data-alokasi') || '';
        let materi = selected.getAttribute('data-materi') || '';
        waktuTambah.value = alokasi;
        materiTambah.value = materi;
    }

    if (babSelectTambah) {
        babSelectTambah.addEventListener('change', updateFromBabTambah);
        if (babSelectTambah.selectedIndex > 0) updateFromBabTambah();
    }

    // ========== MODAL EDIT ==========
    const babSelectEdit = document.getElementById('edit_bab');
    const materiEdit = document.getElementById('edit_materi');
    const waktuEdit = document.getElementById('edit_waktu');

    function updateFromBabEdit() {
        let selected = babSelectEdit.options[babSelectEdit.selectedIndex];
        let alokasi = selected.getAttribute('data-alokasi') || '';
        let materi = selected.getAttribute('data-materi') || '';
        waktuEdit.value = alokasi;
        materiEdit.value = materi;
    }

    if (babSelectEdit) {
        babSelectEdit.addEventListener('change', updateFromBabEdit);
    }

    // Tombol edit: isi data dan set bab, lalu update
    document.querySelectorAll('.btn-edit-atp').forEach(btn => {
        btn.addEventListener('click', function() {
            let id = this.dataset.id;
            let bab_id = this.dataset.bab;
            let alur = this.dataset.alur;
            let materi = this.dataset.materi;
            let semester = this.dataset.semester;
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_alur').value = alur;
            document.getElementById('edit_materi').value = materi;
            document.getElementById('edit_semester').value = semester;
            
            // Set bab yang dipilih
            for (let i = 0; i < babSelectEdit.options.length; i++) {
                if (babSelectEdit.options[i].value == bab_id) {
                    babSelectEdit.selectedIndex = i;
                    break;
                }
            }
            // Update materi dan waktu
            updateFromBabEdit();
            
            document.getElementById('editModal').style.display = 'flex';
        });
    });
});

function tutupModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>