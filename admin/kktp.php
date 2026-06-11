<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Kelola KKTP';
include '../includes/header.php';

// Ambil daftar bab untuk dropdown
$bab_list = mysqli_query($conn, "SELECT id, judul_bab FROM bab ORDER BY id");

// Tambah KKTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $bab_id = (int)$_POST['bab_id'];
    $atp_id = (int)$_POST['atp_id'];
    // Ambil alur tujuan dari tabel atp
    $atp_query = mysqli_query($conn, "SELECT alur_tujuan FROM atp WHERE id=$atp_id");
    $atp_row = mysqli_fetch_assoc($atp_query);
    $alur = mysqli_real_escape_string($conn, $atp_row['alur_tujuan']);
    $skala0 = mysqli_real_escape_string($conn, $_POST['skala0']);
    $skala1 = mysqli_real_escape_string($conn, $_POST['skala1']);
    $skala2 = mysqli_real_escape_string($conn, $_POST['skala2']);
    $skala3 = mysqli_real_escape_string($conn, $_POST['skala3']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    mysqli_query($conn, "INSERT INTO kktp (bab_id, alur_tujuan, skala_0_40, skala_41_65, skala_66_85, skala_86_100, semester) 
                         VALUES ($bab_id, '$alur', '$skala0', '$skala1', '$skala2', '$skala3', '$semester')");
    echo "<script>alert('KKTP berhasil ditambahkan'); window.location.href='kktp';</script>";
}

// Edit KKTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $bab_id = (int)$_POST['bab_id'];
    $atp_id = (int)$_POST['atp_id'];
    $atp_query = mysqli_query($conn, "SELECT alur_tujuan FROM atp WHERE id=$atp_id");
    $atp_row = mysqli_fetch_assoc($atp_query);
    $alur = mysqli_real_escape_string($conn, $atp_row['alur_tujuan']);
    $skala0 = mysqli_real_escape_string($conn, $_POST['skala0']);
    $skala1 = mysqli_real_escape_string($conn, $_POST['skala1']);
    $skala2 = mysqli_real_escape_string($conn, $_POST['skala2']);
    $skala3 = mysqli_real_escape_string($conn, $_POST['skala3']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    mysqli_query($conn, "UPDATE kktp SET bab_id=$bab_id, alur_tujuan='$alur', 
                          skala_0_40='$skala0', skala_41_65='$skala1', 
                          skala_66_85='$skala2', skala_86_100='$skala3', semester='$semester' WHERE id=$id");
    echo "<script>alert('KKTP diupdate'); window.location.href='kktp';</script>";
}

// Hapus KKTP
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM kktp WHERE id=$id");
    echo "<script>alert('KKTP dihapus'); window.location.href='kktp';</script>";
}

// Filter semester
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';
$where = $semester_filter ? "WHERE k.semester='$semester_filter'" : "";
$data = mysqli_query($conn, "SELECT k.*, b.judul_bab 
    FROM kktp k 
    LEFT JOIN bab b ON k.bab_id = b.id 
    $where 
    ORDER BY k.semester, k.id");
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-chart-simple"></i> Kriteria Ketercapaian Tujuan Pembelajaran (KKTP)</h2>
    <p class="page-subtitle">Input KKTP, terintegrasi dengan ATP. Alur tujuan diambil dari data ATP yang sudah ada.</p>
</div>

<!-- Filter Semester -->
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
                <a href="export_kktp_excel?semester=<?= $semester_filter ?>" class="btn btn-outline"><i class="fas fa-file-excel"></i> Export Excel</a>
                <a href="export_kktp_html?semester=<?= $semester_filter ?>" class="btn btn-outline" target="_blank"><i class="fas fa-print"></i> Cetak</a>
            </div>
        </div>
    </form>
</div>

<!-- Form Tambah KKTP -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-plus-circle"></i> Tambah KKTP Baru</div>
    <form method="POST" id="formTambahKKTP">
        <div class="form-row">
            <div class="form-group">
                <label>Bab</label>
                <select name="bab_id" id="bab_id_tambah" class="form-select" required>
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
        </div>
        <div class="form-group">
            <label>Pilih ATP (Alur Tujuan Pembelajaran)</label>
            <select name="atp_id" id="atp_id_tambah" class="form-select" required>
                <option value="">-- Pilih Bab terlebih dahulu --</option>
            </select>
        </div>
        <div class="form-group">
            <label>Alur Tujuan Pembelajaran (otomatis dari ATP)</label>
            <textarea id="alur_tujuan_tampil" class="form-textarea" rows="3" readonly style="background:#f3f4f6;"></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Skala 0-40% (Belum mencapai)</label>
                <input type="text" name="skala0" class="form-input" placeholder="Deskripsi">
            </div>
            <div class="form-group">
                <label>Skala 41-65% (Remedial sebagian)</label>
                <input type="text" name="skala1" class="form-input" placeholder="Deskripsi">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Skala 66-85% (Sudah mencapai)</label>
                <input type="text" name="skala2" class="form-input" placeholder="Deskripsi">
            </div>
            <div class="form-group">
                <label>Skala 86-100% (Pengayaan)</label>
                <input type="text" name="skala3" class="form-input" placeholder="Deskripsi">
            </div>
        </div>
        <button type="submit" name="tambah" class="btn btn-primary"><i class="fas fa-save"></i> Simpan KKTP</button>
    </form>
</div>

<!-- Daftar KKTP -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-list"></i> Daftar KKTP</div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr><th>Bab</th><th>Alur Tujuan</th><th>0-40%</th><th>41-65%</th><th>66-85%</th><th>86-100%</th><th>Semester</th><th>Aksi</th></tr></thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['judul_bab']) ?></strong></td>
                    <td><?= nl2br(htmlspecialchars($row['alur_tujuan'])) ?></td>
                    <td><?= htmlspecialchars($row['skala_0_40']) ?></td>
                    <td><?= htmlspecialchars($row['skala_41_65']) ?></td>
                    <td><?= htmlspecialchars($row['skala_66_85']) ?></td>
                    <td><?= htmlspecialchars($row['skala_86_100']) ?></td>
                    <td><?= $row['semester'] == 1 ? 'Ganjil' : 'Genap' ?></td>
                    <td>
                        <button class="btn btn-sm btn-edit-kktp" data-id="<?= $row['id'] ?>" data-bab="<?= $row['bab_id'] ?>" data-alur="<?= htmlspecialchars($row['alur_tujuan']) ?>" data-semester="<?= $row['semester'] ?>" data-skala0="<?= htmlspecialchars($row['skala_0_40']) ?>" data-skala1="<?= htmlspecialchars($row['skala_41_65']) ?>" data-skala2="<?= htmlspecialchars($row['skala_66_85']) ?>" data-skala3="<?= htmlspecialchars($row['skala_86_100']) ?>"><i class="fas fa-edit"></i> Edit</button>
                        <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus KKTP ini?')"><i class="fas fa-trash"></i> Hapus</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Edit KKTP -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header"><h3>Edit KKTP</h3><button class="modal-close" onclick="tutupModal()">&times;</button></div>
        <form method="POST" id="formEditKKTP">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Bab</label>
                    <select name="bab_id" id="edit_bab" class="form-select" required>
                        <?php 
                        $bab_list2 = mysqli_query($conn, "SELECT id, judul_bab FROM bab ORDER BY id");
                        while($b = mysqli_fetch_assoc($bab_list2)): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['judul_bab']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Pilih ATP</label>
                    <select name="atp_id" id="edit_atp_id" class="form-select" required>
                        <option value="">-- Pilih Bab terlebih dahulu --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Alur Tujuan Pembelajaran (otomatis dari ATP)</label>
                    <textarea id="edit_alur_tampil" class="form-textarea" rows="3" readonly style="background:#f3f4f6;"></textarea>
                </div>
                <div class="form-group">
                    <label>Skala 0-40%</label>
                    <input type="text" name="skala0" id="edit_skala0" class="form-input">
                </div>
                <div class="form-group">
                    <label>Skala 41-65%</label>
                    <input type="text" name="skala1" id="edit_skala1" class="form-input">
                </div>
                <div class="form-group">
                    <label>Skala 66-85%</label>
                    <input type="text" name="skala2" id="edit_skala2" class="form-input">
                </div>
                <div class="form-group">
                    <label>Skala 86-100%</label>
                    <input type="text" name="skala3" id="edit_skala3" class="form-input">
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester" id="edit_semester" class="form-select">
                        <option value="1">Semester 1 (Ganjil)</option>
                        <option value="2">Semester 2 (Genap)</option>
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
    // Fungsi untuk memuat ATP berdasarkan bab (tanpa truncate)
    function loadAtp(babId, selectElement, textareaElement, selectedAtpId = null) {
        if (!babId) {
            selectElement.innerHTML = '<option value="">-- Pilih Bab terlebih dahulu --</option>';
            if (textareaElement) textareaElement.value = '';
            return;
        }
        fetch(`get_atp_by_bab?bab_id=${babId}`)
            .then(response => response.json())
            .then(data => {
                selectElement.innerHTML = '<option value="">-- Pilih ATP --</option>';
                if (data.length === 0) {
                    selectElement.innerHTML = '<option value="">-- Tidak ada ATP untuk bab ini --</option>';
                    if (textareaElement) textareaElement.value = '';
                    return;
                }
                data.forEach(atp => {
                    const option = document.createElement('option');
                    option.value = atp.id;
                    // Tampilkan teks singkat di dropdown (opsional)
                    let shortText = atp.alur_tujuan.substring(0, 80) + (atp.alur_tujuan.length > 80 ? '...' : '');
                    option.textContent = shortText;
                    // Simpan teks lengkap di atribut data-fulltext
                    option.setAttribute('data-fulltext', atp.alur_tujuan);
                    if (selectedAtpId == atp.id) {
                        option.selected = true;
                        if (textareaElement) textareaElement.value = atp.alur_tujuan;
                    }
                    selectElement.appendChild(option);
                });
                // Jika tidak ada selectedAtpId, biarkan textarea kosong
            })
            .catch(error => {
                console.error('Error loading ATP:', error);
                selectElement.innerHTML = '<option value="">-- Gagal memuat ATP --</option>';
            });
    }

    // === FORM TAMBAH ===
    const babSelectTambah = document.getElementById('bab_id_tambah');
    const atpSelectTambah = document.getElementById('atp_id_tambah');
    const alurTampilTambah = document.getElementById('alur_tujuan_tampil');

    if (babSelectTambah) {
        babSelectTambah.addEventListener('change', function() {
            loadAtp(this.value, atpSelectTambah, alurTampilTambah);
        });
        // Ketika dropdown ATP berubah, ambil teks lengkap dari data-fulltext
        atpSelectTambah.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const fullText = selectedOption ? selectedOption.getAttribute('data-fulltext') : '';
            alurTampilTambah.value = fullText || '';
        });
        // Jika bab sudah terpilih (misal karena reload)
        if (babSelectTambah.value) {
            loadAtp(babSelectTambah.value, atpSelectTambah, alurTampilTambah);
        }
    }

    // === MODAL EDIT ===
    const editBabSelect = document.getElementById('edit_bab');
    const editAtpSelect = document.getElementById('edit_atp_id');
    const editAlurTampil = document.getElementById('edit_alur_tampil');

    function loadAtpEdit(babId, selectedAtpId = null) {
        if (!babId) {
            editAtpSelect.innerHTML = '<option value="">-- Pilih Bab terlebih dahulu --</option>';
            editAlurTampil.value = '';
            return;
        }
        fetch(`get_atp_by_bab?bab_id=${babId}`)
            .then(response => response.json())
            .then(data => {
                editAtpSelect.innerHTML = '<option value="">-- Pilih ATP --</option>';
                if (data.length === 0) {
                    editAtpSelect.innerHTML = '<option value="">-- Tidak ada ATP untuk bab ini --</option>';
                    return;
                }
                data.forEach(atp => {
                    const option = document.createElement('option');
                    option.value = atp.id;
                    let shortText = atp.alur_tujuan.substring(0, 80) + (atp.alur_tujuan.length > 80 ? '...' : '');
                    option.textContent = shortText;
                    option.setAttribute('data-fulltext', atp.alur_tujuan);
                    if (selectedAtpId == atp.id) {
                        option.selected = true;
                        editAlurTampil.value = atp.alur_tujuan;
                    }
                    editAtpSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading ATP for edit:', error));
    }

    if (editBabSelect) {
        editBabSelect.addEventListener('change', function() {
            loadAtpEdit(this.value);
        });
        editAtpSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const fullText = selectedOption ? selectedOption.getAttribute('data-fulltext') : '';
            editAlurTampil.value = fullText || '';
        });
    }

    // Tombol edit di tabel
    document.querySelectorAll('.btn-edit-kktp').forEach(btn => {
        btn.addEventListener('click', function() {
            let id = this.dataset.id;
            let bab_id = this.dataset.bab;
            let semester = this.dataset.semester;
            let skala0 = this.dataset.skala0;
            let skala1 = this.dataset.skala1;
            let skala2 = this.dataset.skala2;
            let skala3 = this.dataset.skala3;
            let alurTeks = this.dataset.alur;

            document.getElementById('edit_id').value = id;
            document.getElementById('edit_skala0').value = skala0;
            document.getElementById('edit_skala1').value = skala1;
            document.getElementById('edit_skala2').value = skala2;
            document.getElementById('edit_skala3').value = skala3;
            document.getElementById('edit_semester').value = semester;
            editAlurTampil.value = alurTeks;

            // Set bab select
            for (let i = 0; i < editBabSelect.options.length; i++) {
                if (editBabSelect.options[i].value == bab_id) {
                    editBabSelect.selectedIndex = i;
                    break;
                }
            }
            // Load ATP tanpa auto-select (karena kita tidak tahu ATP mana yang dipakai sebelumnya)
            loadAtpEdit(bab_id);
            document.getElementById('editModal').style.display = 'flex';
        });
    });
});

function tutupModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>