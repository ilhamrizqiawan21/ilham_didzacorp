<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Pengaturan Sistem';
include '../includes/header.php';

// Proses simpan pengaturan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_pengaturan'])) {
    $tahun = mysqli_real_escape_string($conn, $_POST['tahun_ajaran']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $warna_tema = mysqli_real_escape_string($conn, $_POST['warna_tema']);
    
    set_pengaturan($conn, 'tahun_ajaran_aktif', $tahun);
    set_pengaturan($conn, 'semester_aktif', $semester);
    set_pengaturan($conn, 'warna_tema', $warna_tema);
    
    echo "<script>alert('Pengaturan berhasil disimpan'); window.location.href='pengaturan';</script>";
}

// Ambil data pengaturan saat ini
$tahun_aktif = get_tahun_ajaran_aktif($conn);
$semester_aktif = get_semester_aktif($conn);
$warna_tema = get_pengaturan($conn, 'warna_tema');
if (!$warna_tema) $warna_tema = 'hijau';
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-sliders-h"></i> Pengaturan Sistem</h2>
    <p class="page-subtitle">Atur tahun ajaran, semester aktif, dan tampilan warna website.</p>
</div>

<div class="form-container">
    <form method="POST">
        <div class="form-title"><i class="fas fa-calendar-alt"></i> Pengaturan Akademik</div>
        <div class="form-row">
            <div class="form-group">
                <label>Tahun Ajaran Aktif</label>
                <select name="tahun_ajaran" class="form-select">
                    <option value="2024/2025" <?= $tahun_aktif == '2024/2025' ? 'selected' : '' ?>>2024/2025</option>
                    <option value="2025/2026" <?= $tahun_aktif == '2025/2026' ? 'selected' : '' ?>>2025/2026</option>
                    <option value="2026/2027" <?= $tahun_aktif == '2026/2027' ? 'selected' : '' ?>>2026/2027</option>
                </select>
            </div>
            <div class="form-group">
                <label>Semester Aktif</label>
                <select name="semester" class="form-select">
                    <option value="1" <?= $semester_aktif == '1' ? 'selected' : '' ?>>Semester 1</option>
                    <option value="2" <?= $semester_aktif == '2' ? 'selected' : '' ?>>Semester 2</option>
                </select>
            </div>
        </div>
        
        <div class="form-title" style="margin-top:1rem;"><i class="fas fa-palette"></i> Tampilan Warna</div>
        <div class="form-group">
            <label>Pilih Warna Tema</label>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <label style="display:flex; align-items:center; gap:5px;">
                    <input type="radio" name="warna_tema" value="hijau" <?= $warna_tema == 'hijau' ? 'checked' : '' ?>> 
                    <span style="display:inline-block; width:20px; height:20px; background:#059669; border-radius:4px;"></span> Hijau (Default)
                </label>
                <label style="display:flex; align-items:center; gap:5px;">
                    <input type="radio" name="warna_tema" value="biru-azure" <?= $warna_tema == 'biru-azure' ? 'checked' : '' ?>> 
                    <span style="display:inline-block; width:20px; height:20px; background:#0078D7; border-radius:4px;"></span> Biru Azure
                </label>
                <label style="display:flex; align-items:center; gap:5px;">
                    <input type="radio" name="warna_tema" value="hijau-muda" <?= $warna_tema == 'hijau-muda' ? 'checked' : '' ?>> 
                    <span style="display:inline-block; width:20px; height:20px; background:#4ade80; border-radius:4px;"></span> Hijau Muda
                </label>
                <label style="display:flex; align-items:center; gap:5px;">
                    <input type="radio" name="warna_tema" value="biru-aqua" <?= $warna_tema == 'biru-aqua' ? 'checked' : '' ?>> 
                    <span style="display:inline-block; width:20px; height:20px; background:#00b4d8; border-radius:4px;"></span> Biru Aqua
                </label>
            </div>
        </div>
        
        <button type="submit" name="simpan_pengaturan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Pengaturan</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>