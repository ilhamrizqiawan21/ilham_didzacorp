<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Kelola Bab';
include '../includes/header.php';

// ========== FUNGSI PREVIEW ==========
function get_file_icon($ext) {
    $icons = [
        'pdf' => 'fa-file-pdf',
        'doc' => 'fa-file-word', 'docx' => 'fa-file-word',
        'xls' => 'fa-file-excel', 'xlsx' => 'fa-file-excel',
        'ppt' => 'fa-file-powerpoint', 'pptx' => 'fa-file-powerpoint',
        'mp4' => 'fa-file-video', 'avi' => 'fa-file-video', 'mov' => 'fa-file-video',
        'jpg' => 'fa-file-image', 'jpeg' => 'fa-file-image', 'png' => 'fa-file-image', 'gif' => 'fa-file-image',
        'zip' => 'fa-file-archive', 'rar' => 'fa-file-archive',
        'txt' => 'fa-file-alt',
    ];
    return $icons[$ext] ?? 'fa-file';
}

// Tambah bab
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan']);
    $materi_pokok = mysqli_real_escape_string($conn, $_POST['materi_pokok']);
    $alokasi_waktu = mysqli_real_escape_string($conn, $_POST['alokasi_waktu']);
    $target_kelas = isset($_POST['target_kelas']) && is_array($_POST['target_kelas']) ? implode(',', array_map('intval', $_POST['target_kelas'])) : '';
    $file_name = null;
    if ($_FILES['file_materi']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['file_materi']['name'], PATHINFO_EXTENSION));
        $file_name = time() . '.' . $ext;
        move_uploaded_file($_FILES['file_materi']['tmp_name'], UPLOAD_MATERI . $file_name);
    }
    mysqli_query($conn, "INSERT INTO bab (judul_bab, tujuan_pembelajaran, materi_pokok, alokasi_waktu, file_materi, target_kelas) VALUES ('$judul', '$tujuan', '$materi_pokok', '$alokasi_waktu', '$file_name', '$target_kelas')");
    echo "<script>alert('Bab berhasil ditambahkan'); window.location.href='bab';</script>";
}

// Edit bab
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = (int)$_POST['id'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $tujuan = mysqli_real_escape_string($conn, $_POST['tujuan']);
    $materi_pokok = mysqli_real_escape_string($conn, $_POST['materi_pokok']);
    $alokasi_waktu = mysqli_real_escape_string($conn, $_POST['alokasi_waktu']);
    $target_kelas = isset($_POST['target_kelas']) && is_array($_POST['target_kelas']) ? implode(',', array_map('intval', $_POST['target_kelas'])) : '';
    if ($_FILES['file_materi']['error'] == 0) {
        $q = "SELECT file_materi FROM bab WHERE id=$id";
        $old = mysqli_fetch_assoc(mysqli_query($conn, $q));
        if ($old['file_materi'] && file_exists(UPLOAD_MATERI . $old['file_materi'])) unlink(UPLOAD_MATERI . $old['file_materi']);
        $ext = strtolower(pathinfo($_FILES['file_materi']['name'], PATHINFO_EXTENSION));
        $file_name = time() . '.' . $ext;
        move_uploaded_file($_FILES['file_materi']['tmp_name'], UPLOAD_MATERI . $file_name);
        mysqli_query($conn, "UPDATE bab SET judul_bab='$judul', tujuan_pembelajaran='$tujuan', materi_pokok='$materi_pokok', alokasi_waktu='$alokasi_waktu', file_materi='$file_name', target_kelas='$target_kelas' WHERE id=$id");
    } else {
        mysqli_query($conn, "UPDATE bab SET judul_bab='$judul', tujuan_pembelajaran='$tujuan', materi_pokok='$materi_pokok', alokasi_waktu='$alokasi_waktu', target_kelas='$target_kelas' WHERE id=$id");
    }
    echo "<script>alert('Bab diupdate'); window.location.href='bab';</script>";
}

// Hapus bab
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT file_materi FROM bab WHERE id=$id"));
    if ($row['file_materi'] && file_exists(UPLOAD_MATERI . $row['file_materi'])) unlink(UPLOAD_MATERI . $row['file_materi']);
    mysqli_query($conn, "DELETE FROM bab WHERE id=$id");
    echo "<script>alert('Bab dihapus'); window.location.href='bab';</script>";
}

$bab = mysqli_query($conn, "SELECT * FROM bab ORDER BY id");
$all_kelas = mysqli_query($conn, "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-book"></i> Kelola Bab</h2>
    <p class="page-subtitle">Tambah, edit, atau hapus bab pembelajaran. Tentukan kelas sasaran untuk setiap bab.</p>
</div>

<!-- Form Tambah Bab Baru -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-plus-circle"></i> Tambah Bab Baru</div>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-row">
            <div class="form-group">
                <label class="required">Judul Bab</label>
                <input type="text" name="judul" class="form-input" required>
            </div>
            <div class="form-group">
                <label>Alokasi Waktu</label>
                <input type="text" name="alokasi_waktu" class="form-input" placeholder="Contoh: 4 JP">
            </div>
        </div>
        <div class="form-group">
            <label>Tujuan Pembelajaran</label>
            <textarea name="tujuan" class="form-textarea" rows="2"></textarea>
        </div>
        <div class="form-group">
            <label>Materi Pokok</label>
            <textarea name="materi_pokok" class="form-textarea" rows="2"></textarea>
        </div>
        <div class="form-group">
            <label>File Materi (PDF/DOCX/PPT/MP4/ZIP/dll)</label>
            <input type="file" name="file_materi" class="form-input">
        </div>
        <div class="form-group">
            <label>Kelas Sasaran (centang kelas yang boleh mengakses bab ini)</label>
            <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 8px;">
                <?php while($k = mysqli_fetch_assoc($all_kelas)): ?>
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" name="target_kelas[]" value="<?= $k['id'] ?>"> <?= htmlspecialchars($k['nama_kelas']) ?>
                    </label>
                <?php endwhile; ?>
            </div>
            <small class="text-muted">Kosongkan berarti semua kelas.</small>
        </div>
        <button type="submit" name="tambah" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Bab</button>
    </form>
</div>

<!-- Daftar Bab -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-list"></i> Daftar Bab</div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>#</th><th>Judul Bab</th><th>Tujuan</th><th>Materi Pokok</th><th>Waktu</th><th>File</th><th>Kelas Sasaran</th><th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($b = mysqli_fetch_assoc($bab)): 
                    $file = $b['file_materi'];
                    $ext = $file ? strtolower(pathinfo($file, PATHINFO_EXTENSION)) : '';
                    $icon = get_file_icon($ext);
                    // Ambil nama kelas dari target_kelas
                    $target_kelas_ids = $b['target_kelas'] ? explode(',', $b['target_kelas']) : [];
                    $target_kelas_nama = [];
                    if (!empty($target_kelas_ids)) {
                        $ids = implode(',', array_map('intval', $target_kelas_ids));
                        $query_kelas = mysqli_query($conn, "SELECT nama_kelas FROM kelas WHERE id IN ($ids)");
                        while($kel = mysqli_fetch_assoc($query_kelas)) {
                            $target_kelas_nama[] = $kel['nama_kelas'];
                        }
                    }
                    $target_tampil = empty($target_kelas_nama) ? 'Semua Kelas' : implode(', ', $target_kelas_nama);
                    // Simpan data bab dalam bentuk JSON untuk JavaScript
                    $data_bab = [
                        'id' => $b['id'],
                        'judul' => $b['judul_bab'],
                        'tujuan' => $b['tujuan_pembelajaran'],
                        'materi' => $b['materi_pokok'],
                        'waktu' => $b['alokasi_waktu'],
                        'target_kelas' => $b['target_kelas']
                    ];
                ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td><strong><?= htmlspecialchars($b['judul_bab']) ?></strong></td>
                    <td><?= nl2br(htmlspecialchars(substr($b['tujuan_pembelajaran'],0,100))) ?>...</td>
                    <td><?= nl2br(htmlspecialchars(substr($b['materi_pokok'],0,100))) ?>...</td>
                    <td><?= $b['alokasi_waktu'] ?></td>
                    <td>
                        <?php if($file): ?>
                            <i class="fas <?= $icon ?>"></i> <?= $file ?>
                        <?php else: ?> - <?php endif; ?>
                    </td>
                    <td style="max-width: 200px;"><?= htmlspecialchars($target_tampil) ?></td>
                    <td class="action-cell">
                        <div class="action-buttons">
                            <?php if($file): ?>
                                <button class="btn btn-sm btn-preview btn-action" data-file="<?= $file ?>" data-ext="<?= $ext ?>"><i class="fas fa-eye"></i> Lihat</button>
                                <a href="preview_materi?id=<?= $b['id'] ?>" target="_blank" class="btn btn-sm btn-outline btn-action"><i class="fas fa-tv"></i> Proyektor</a>
                            <?php endif; ?>
                            <!-- Tombol Edit menggunakan data attribute (aman untuk karakter khusus) -->
                            <button class="btn btn-sm btn-edit btn-action btn-edit-bab" data-bab='<?= json_encode($data_bab, JSON_HEX_QUOT | JSON_HEX_APOS) ?>'>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="?hapus=<?= $b['id'] ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Yakin hapus bab ini?')"><i class="fas fa-trash"></i> Hapus</a>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Preview Materi -->
<div id="previewModal" class="modal">
    <div class="modal-content" style="max-width: 90%; width: 1000px;">
        <div class="modal-header">
            <h3><i class="fas fa-file-alt"></i> Preview Materi</h3>
            <button class="modal-close" onclick="closePreviewModal()">&times;</button>
        </div>
        <div class="modal-body" id="previewBody" style="min-height: 500px;">
            <div style="text-align: center;">Memuat...</div>
        </div>
        <div class="modal-footer">
            <a href="#" id="downloadLink" class="btn btn-primary" download><i class="fas fa-download"></i> Download</a>
            <button type="button" class="btn" onclick="closePreviewModal()">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal Edit Bab -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit Bab</h3>
            <button class="modal-close" onclick="tutupModal()">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Judul Bab</label>
                    <input type="text" name="judul" id="edit_judul" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Tujuan Pembelajaran</label>
                    <textarea name="tujuan" id="edit_tujuan" class="form-textarea" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Materi Pokok</label>
                    <textarea name="materi_pokok" id="edit_materi" class="form-textarea" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label>Alokasi Waktu</label>
                    <input type="text" name="alokasi_waktu" id="edit_waktu" class="form-input">
                </div>
                <div class="form-group">
                    <label>Kelas Sasaran</label>
                    <div id="edit_target_kelas" style="display: flex; flex-wrap: wrap; gap: 12px;">
                        <?php 
                        $kelas_edit = mysqli_query($conn, "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
                        while($k = mysqli_fetch_assoc($kelas_edit)): ?>
                            <label style="display: flex; align-items: center; gap: 5px;">
                                <input type="checkbox" name="target_kelas[]" value="<?= $k['id'] ?>" class="edit-kelas-checkbox"> <?= htmlspecialchars($k['nama_kelas']) ?>
                            </label>
                        <?php endwhile; ?>
                    </div>
                    <small class="text-muted">Kosongkan berarti semua kelas.</small>
                </div>
                <div class="form-group">
                    <label>Ganti File Materi (opsional)</label>
                    <input type="file" name="file_materi" class="form-input">
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
// ========== PREVIEW FILE (sama seperti sebelumnya) ==========
document.querySelectorAll('.btn-preview').forEach(btn => {
    btn.addEventListener('click', function() {
        let filename = this.dataset.file;
        let ext = this.dataset.ext;
        let fileUrl = '../uploads/materi/' + filename;
        let previewHtml = '';
        let downloadUrl = fileUrl;

        if (ext === 'pdf') {
            previewHtml = `<embed src="${fileUrl}" width="100%" height="550px" type="application/pdf">`;
        } 
        else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
            previewHtml = `<img src="${fileUrl}" style="max-width:100%; max-height:500px; display:block; margin:auto;">`;
        }
        else if (['mp4', 'webm', 'ogg'].includes(ext)) {
            previewHtml = `<video controls style="width:100%; max-height:500px;"><source src="${fileUrl}" type="video/${ext}">Browser tidak support video.</video>`;
        }
        else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(ext)) {
            let googleUrl = `https://docs.google.com/gview?url=${encodeURIComponent(fileUrl)}&embedded=true`;
            previewHtml = `<iframe src="${googleUrl}" width="100%" height="550px" style="border:none;"></iframe>`;
        }
        else {
            previewHtml = `<div style="text-align:center; padding:40px;">
                            <i class="fas fa-file fa-4x"></i>
                            <p>File tidak dapat dipreview langsung.</p>
                            <a href="${fileUrl}" class="btn btn-primary" download>Download File</a>
                           </div>`;
        }

        document.getElementById('previewBody').innerHTML = previewHtml;
        document.getElementById('downloadLink').href = downloadUrl;
        document.getElementById('previewModal').style.display = 'flex';
    });
});

// ========== EDIT BAB (menggunakan data attribute) ==========
document.querySelectorAll('.btn-edit-bab').forEach(btn => {
    btn.addEventListener('click', function() {
        let bab = JSON.parse(this.dataset.bab);
        document.getElementById('edit_id').value = bab.id;
        document.getElementById('edit_judul').value = bab.judul;
        document.getElementById('edit_tujuan').value = bab.tujuan;
        document.getElementById('edit_materi').value = bab.materi;
        document.getElementById('edit_waktu').value = bab.waktu;
        
        // Reset checkbox kelas
        document.querySelectorAll('.edit-kelas-checkbox').forEach(cb => cb.checked = false);
        if (bab.target_kelas) {
            let ids = bab.target_kelas.split(',');
            ids.forEach(id => {
                let cb = document.querySelector(`.edit-kelas-checkbox[value="${id}"]`);
                if (cb) cb.checked = true;
            });
        }
        document.getElementById('editModal').style.display = 'flex';
    });
});

function tutupModal() {
    document.getElementById('editModal').style.display = 'none';
}
function closePreviewModal() {
    document.getElementById('previewModal').style.display = 'none';
    document.getElementById('previewBody').innerHTML = '<div style="text-align: center;">Memuat...</div>';
}

// Tutup modal jika klik di luar
window.onclick = function(event) {
    let modal = document.getElementById('editModal');
    let previewModal = document.getElementById('previewModal');
    if (event.target == modal) modal.style.display = 'none';
    if (event.target == previewModal) closePreviewModal();
}
</script>

<style>
/* PERBAIKAN TOMBOL AKSI - RAPI DAN SERAGAM */
.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}
.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 6px 14px;
    font-size: 0.7rem;
    font-weight: 500;
    border-radius: 20px;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    min-width: 70px;
    white-space: nowrap;
}
.btn-action i {
    font-size: 0.75rem;
}
.btn-preview {
    background-color: #eef2ff;
    color: #4338ca;
    border: 1px solid #c7d2fe;
}
.btn-preview:hover {
    background-color: #4338ca;
    color: white;
}
.btn-outline {
    background-color: #f3f4f6;
    color: #4b5563;
    border: 1px solid #d1d5db;
}
.btn-outline:hover {
    background-color: #e5e7eb;
    border-color: #9ca3af;
}
.btn-edit {
    background-color: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}
.btn-edit:hover {
    background-color: #10b981;
    color: white;
}
.btn-danger {
    background-color: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}
.btn-danger:hover {
    background-color: #dc2626;
    color: white;
}
/* Responsif */
@media (max-width: 768px) {
    .btn-action {
        padding: 4px 10px;
        font-size: 0.65rem;
        min-width: 60px;
    }
    .action-buttons {
        gap: 5px;
    }
}
</style>

<?php include '../includes/footer.php'; ?>