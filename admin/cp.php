<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Kelola CP';
include '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah'])) {
        $elemen = mysqli_real_escape_string($conn, $_POST['elemen']);
        $capaian = mysqli_real_escape_string($conn, $_POST['capaian']);
        $semester = mysqli_real_escape_string($conn, $_POST['semester']);
        mysqli_query($conn, "INSERT INTO cp (elemen, capaian_pembelajaran, semester) VALUES ('$elemen', '$capaian', '$semester')");
        echo "<script>alert('CP ditambahkan'); window.location.href='cp';</script>";
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $elemen = mysqli_real_escape_string($conn, $_POST['elemen']);
        $capaian = mysqli_real_escape_string($conn, $_POST['capaian']);
        $semester = mysqli_real_escape_string($conn, $_POST['semester']);
        mysqli_query($conn, "UPDATE cp SET elemen='$elemen', capaian_pembelajaran='$capaian', semester='$semester' WHERE id=$id");
        echo "<script>alert('CP diupdate'); window.location.href='cp';</script>";
    }
}
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM cp WHERE id=$id");
    echo "<script>alert('CP dihapus'); window.location.href='cp';</script>";
}
$data = mysqli_query($conn, "SELECT * FROM cp ORDER BY semester, id");
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-bullseye"></i> Capaian Pembelajaran (CP)</h2>
    <p class="page-subtitle">Input CP berdasarkan elemen dan capaian pembelajaran.</p>
</div>

<div class="form-container">
    <div class="form-title"><i class="fas fa-plus"></i> Tambah CP Baru</div>
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Elemen</label>
                <input type="text" name="elemen" class="form-input" required placeholder="Contoh: Tajwid, Hadis">
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
            <label>Capaian Pembelajaran</label>
            <textarea name="capaian" class="form-textarea" rows="4" required></textarea>
        </div>
        <button type="submit" name="tambah" class="btn btn-primary">Simpan CP</button>
    </form>
</div>

<div class="form-container">
    <div class="form-title"><i class="fas fa-list"></i> Daftar CP</div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead><tr><th>Elemen</th><th>Capaian Pembelajaran</th><th>Semester</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['elemen']) ?></strong></td>
                    <td><?= nl2br(htmlspecialchars($row['capaian_pembelajaran'])) ?></td>
                    <td><?= $row['semester'] == 1 ? 'Ganjil' : 'Genap' ?></td>
                    <td>
                        <button class="btn btn-sm" onclick="editCP(<?= $row['id'] ?>, '<?= addslashes($row['elemen']) ?>', '<?= addslashes($row['capaian_pembelajaran']) ?>', '<?= $row['semester'] ?>')"><i class="fas fa-edit"></i> Edit</button>
                        <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus?')"><i class="fas fa-trash"></i> Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Edit CP</h3><button class="modal-close" onclick="tutupModal()">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group"><label>Elemen</label><input type="text" name="elemen" id="edit_elemen" class="form-input"></div>
                <div class="form-group"><label>Semester</label><select name="semester" id="edit_semester" class="form-select"><option value="1">Semester 1</option><option value="2">Semester 2</option></select></div>
                <div class="form-group"><label>Capaian Pembelajaran</label><textarea name="capaian" id="edit_capaian" class="form-textarea" rows="4"></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" name="edit" class="btn btn-primary">Update</button><button type="button" class="btn" onclick="tutupModal()">Batal</button></div>
        </form>
    </div>
</div>

<script>
function editCP(id, elemen, capaian, semester) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_elemen').value = elemen;
    document.getElementById('edit_capaian').value = capaian;
    document.getElementById('edit_semester').value = semester;
    document.getElementById('editModal').style.display = 'flex';
}
function tutupModal() { document.getElementById('editModal').style.display = 'none'; }
</script>

<?php include '../includes/footer.php'; ?>