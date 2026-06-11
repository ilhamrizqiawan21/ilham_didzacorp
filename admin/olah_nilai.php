<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Olah Nilai (Raport)';
include '../includes/header.php';

// ========== PROSES UPDATE NILAI ==========
// Hanya untuk STS, SAS, SAT (SUM tidak diupdate dari sini)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_nilai_akhir'])) {
    $kelas_id = (int)$_POST['kelas_id'];
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $tahun_ajaran = mysqli_real_escape_string($conn, $_POST['tahun_ajaran']);
    foreach ($_POST['sts'] as $siswa_id => $nilai) {
        // SUM tidak disertakan dalam update
        $sts = isset($_POST['sts'][$siswa_id]) && $_POST['sts'][$siswa_id] !== '' ? (float)$_POST['sts'][$siswa_id] : 'NULL';
        $sas_asli = isset($_POST['sas_asli'][$siswa_id]) && $_POST['sas_asli'][$siswa_id] !== '' ? (float)$_POST['sas_asli'][$siswa_id] : 'NULL';
        $sas_jadi = isset($_POST['sas_jadi'][$siswa_id]) && $_POST['sas_jadi'][$siswa_id] !== '' ? (float)$_POST['sas_jadi'][$siswa_id] : 'NULL';
        $sat_asli = isset($_POST['sat_asli'][$siswa_id]) && $_POST['sat_asli'][$siswa_id] !== '' ? (float)$_POST['sat_asli'][$siswa_id] : 'NULL';
        $sat_jadi = isset($_POST['sat_jadi'][$siswa_id]) && $_POST['sat_jadi'][$siswa_id] !== '' ? (float)$_POST['sat_jadi'][$siswa_id] : 'NULL';
        
        $cek = mysqli_query($conn, "SELECT id FROM nilai_akhir WHERE siswa_id=$siswa_id AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'");
        if (mysqli_num_rows($cek)) {
            mysqli_query($conn, "UPDATE nilai_akhir SET 
                sts = $sts, sas_asli = $sas_asli, sas_jadi = $sas_jadi,
                sat_asli = $sat_asli, sat_jadi = $sat_jadi
                WHERE siswa_id=$siswa_id AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'");
        } else {
            // Insert hanya untuk kolom STS, SAS, SAT (SUM tetap NULL)
            mysqli_query($conn, "INSERT INTO nilai_akhir (siswa_id, semester, tahun_ajaran, sts, sas_asli, sas_jadi, sat_asli, sat_jadi) 
                VALUES ($siswa_id, '$semester', '$tahun_ajaran', $sts, $sas_asli, $sas_jadi, $sat_asli, $sat_jadi)");
        }
    }
    echo "<script>alert('Nilai STS, SAS, SAT berhasil disimpan'); window.location.href='olah_nilai?kelas_id=$kelas_id&semester=$semester&tahun=$tahun_ajaran';</script>";
}

// ========== FILTER DAN TAMPILAN ==========
$kelas_list = mysqli_query($conn, "SELECT * FROM kelas ORDER BY nama_kelas");
$selected_kelas = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : (isset($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : 0);

// Ambil pengaturan semester dan tahun ajaran aktif
$tahun_aktif = get_tahun_ajaran_aktif($conn);
$semester_aktif = get_semester_aktif($conn);
if (!$tahun_aktif) $tahun_aktif = date('Y') . '/' . (date('Y')+1);
if (!$semester_aktif) $semester_aktif = (date('m') >= 7) ? '1' : '2';

$semester = isset($_GET['semester']) ? $_GET['semester'] : (isset($_POST['semester']) ? $_POST['semester'] : $semester_aktif);
$tahun_ajaran = isset($_GET['tahun']) ? $_GET['tahun'] : (isset($_POST['tahun_ajaran']) ? $_POST['tahun_ajaran'] : $tahun_aktif);

$siswa_data = [];
if ($selected_kelas) {
    $siswa_data = mysqli_query($conn, "SELECT s.id, s.nis, s.nama, s.jenis_kelamin, k.nama_kelas 
        FROM siswa s 
        JOIN kelas k ON s.kelas_id = k.id 
        WHERE s.kelas_id = $selected_kelas 
        ORDER BY s.nama");
}
?>

<style>
/* Perbaikan tampilan tabel nilai */
.nilai-table {
    min-width: 1000px;
}
.nilai-table th, .nilai-table td {
    padding: 8px 6px;
    vertical-align: middle;
    text-align: center;
}
.nilai-table th {
    background: #f1f5f9;
    font-weight: 600;
    font-size: 0.8rem;
}
.nilai-table td input {
    width: 70px;
    padding: 6px;
    text-align: center;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    transition: all 0.2s;
}
.nilai-table td input:focus {
    border-color: #10b981;
    outline: none;
    box-shadow: 0 0 0 2px rgba(16,185,129,0.2);
}
.nilai-table td input[readonly] {
    background-color: #f8fafc;
    border-color: #e2e8f0;
    cursor: not-allowed;
}
.nilai-table .siswa-nama {
    text-align: left;
    font-weight: 500;
}
@media (max-width: 768px) {
    .nilai-table td input {
        width: 55px;
        padding: 4px;
        font-size: 12px;
    }
    .nilai-table th, .nilai-table td {
        padding: 4px;
        font-size: 11px;
    }
}
.info-note {
    background: #e6f7ff;
    border-left: 4px solid #1890ff;
    padding: 8px 12px;
    margin-bottom: 1rem;
    border-radius: 6px;
    font-size: 0.8rem;
}
.alert-box {
    padding: 10px 14px;
    border-radius: 6px;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}
.alert-box.success {
    background: #ecfdf5;
    border: 1px solid #10b981;
    color: #065f46;
}
.alert-box.danger {
    background: #fef2f2;
    border: 1px solid #ef4444;
    color: #991b1b;
}
.alert-box.info {
    background: #eff6ff;
    border: 1px solid #3b82f6;
    color: #1d4ed8;
}
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-chart-line"></i> Olah Nilai (Raport)</h2>
    <p class="page-subtitle">Input nilai STS, SAS, SAT. Nilai SUM 1-4 otomatis terisi dari menu Tugas dan tidak dapat diedit di sini.</p>
</div>

<div class="form-container">
    <form method="GET" class="form-row">
        <div class="form-group">
            <label>Pilih Kelas</label>
            <select name="kelas_id" class="form-select" required>
                <option value="0">-- Pilih Kelas --</option>
                <?php while($k = mysqli_fetch_assoc($kelas_list)): ?>
                    <option value="<?= $k['id'] ?>" <?= $selected_kelas == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Semester</label>
            <select name="semester" class="form-select">
                <option value="1" <?= $semester == '1' ? 'selected' : '' ?>>Semester 1</option>
                <option value="2" <?= $semester == '2' ? 'selected' : '' ?>>Semester 2</option>
            </select>
        </div>
        <div class="form-group">
            <label>Tahun Ajaran</label>
            <select name="tahun" class="form-select">
                <option value="2024/2025" <?= $tahun_ajaran == '2024/2025' ? 'selected' : '' ?>>2024/2025</option>
                <option value="2025/2026" <?= $tahun_ajaran == '2025/2026' ? 'selected' : '' ?>>2025/2026</option>
                <option value="2026/2027" <?= $tahun_ajaran == '2026/2027' ? 'selected' : '' ?>>2026/2027</option>
                <option value="2027/2028">2027/2028</option>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Tampilkan</button>
        </div>
    </form>
    <p class="text-muted" style="margin-top:0.5rem; font-size:0.8rem;">Semester dan tahun ajaran default mengikuti pengaturan di Dashboard. Gunakan filter di atas untuk mengubah.</p>
</div>

<?php if($selected_kelas && mysqli_num_rows($siswa_data) > 0): ?>
<div class="form-container" style="overflow-x: auto;">
    <div class="info-note">
        <i class="fas fa-info-circle"></i> <strong>Informasi:</strong> Nilai SUM 1-4 diambil dari rata-rata nilai tugas yang dikategorikan SUM1, SUM2, dst. Nilai ini bersifat <strong>readonly</strong> dan hanya dapat diubah melalui menu <strong>Penugasan & Nilai SUM</strong>. Untuk STS, SAS, SAT silakan diisi manual.
    </div>
    <div class="info-note" style="background:#fff7e6;border-left-color:#f59e0b;">
        <i class="fas fa-clipboard"></i> <strong>Tip Paste Excel:</strong> Salin beberapa baris dari Excel, pilih sel input STS/SAS/SAT pertama, lalu tekan <strong>Ctrl+V</strong>. Nilai akan terisi otomatis ke baris dan kolom berikutnya.
    </div>
    <form method="POST">
        <input type="hidden" name="kelas_id" value="<?= $selected_kelas ?>">
        <input type="hidden" name="semester" value="<?= $semester ?>">
        <input type="hidden" name="tahun_ajaran" value="<?= $tahun_ajaran ?>">
        <table class="modern-table nilai-table" id="nilaiTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIS</th>
                    <th>Nama Siswa</th>
                    <th>L/P</th>
                    <th>SUM 1</th>
                    <th>SUM 2</th>
                    <th>SUM 3</th>
                    <th>SUM 4</th>
                    <th>STS</th>
                    <th>SAS Asli</th>
                    <th>SAS Jadi</th>
                    <th>SAT Asli</th>
                    <th>SAT Jadi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                while($s = mysqli_fetch_assoc($siswa_data)):
                    $nilai = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM nilai_akhir WHERE siswa_id={$s['id']} AND semester='$semester' AND tahun_ajaran='$tahun_ajaran'"));
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($s['nis']) ?></td>
                    <td class="siswa-nama"><?= htmlspecialchars($s['nama']) ?></td>
                    <td><?= $s['jenis_kelamin'] ?></td>
                    <!-- SUM1-4 readonly -->
                    <td><input type="text" readonly value="<?= isset($nilai['sum1']) ? number_format($nilai['sum1'], 2) : '-' ?>"></td>
                    <td><input type="text" readonly value="<?= isset($nilai['sum2']) ? number_format($nilai['sum2'], 2) : '-' ?>"></td>
                    <td><input type="text" readonly value="<?= isset($nilai['sum3']) ? number_format($nilai['sum3'], 2) : '-' ?>"></td>
                    <td><input type="text" readonly value="<?= isset($nilai['sum4']) ? number_format($nilai['sum4'], 2) : '-' ?>"></td>
                    <!-- STS, SAS, SAT dapat diedit -->
                    <td><input type="number" name="sts[<?= $s['id'] ?>]" value="<?= $nilai['sts'] ?? '' ?>" step="0.01" min="0" max="100" placeholder="0"></td>
                    <td><input type="number" name="sas_asli[<?= $s['id'] ?>]" value="<?= $nilai['sas_asli'] ?? '' ?>" step="0.01" min="0" max="100" placeholder="0"></td>
                    <td><input type="number" name="sas_jadi[<?= $s['id'] ?>]" value="<?= $nilai['sas_jadi'] ?? '' ?>" step="0.01" min="0" max="100" placeholder="0"></td>
                    <td><input type="number" name="sat_asli[<?= $s['id'] ?>]" value="<?= $nilai['sat_asli'] ?? '' ?>" step="0.01" min="0" max="100" placeholder="0"></td>
                    <td><input type="number" name="sat_jadi[<?= $s['id'] ?>]" value="<?= $nilai['sat_jadi'] ?? '' ?>" step="0.01" min="0" max="100" placeholder="0"></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="btn-group" style="margin-top: 1rem;">
            <button type="submit" name="update_nilai_akhir" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Nilai STS, SAS, SAT</button>
        </div>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const nilaiTable = document.getElementById('nilaiTable');
        if (!nilaiTable) return;

        const editableNames = ['sts', 'sas_asli', 'sas_jadi', 'sat_asli', 'sat_jadi'];
        const getRowInputs = (row) => editableNames.map(name => row.querySelector(`input[name^="${name}["`]));

        nilaiTable.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('paste', function(e) {
                const clipboard = (e.clipboardData || window.clipboardData).getData('text/plain');
                if (!clipboard) return;
                e.preventDefault();

                const rows = clipboard.replace(/\r/g, '').split('\n').filter(r => r.trim() !== '');
                const currentRow = e.target.closest('tr');
                const tbodyRows = Array.from(nilaiTable.querySelectorAll('tbody tr'));
                const startRowIndex = tbodyRows.indexOf(currentRow);
                if (startRowIndex < 0) return;

                const startMatch = e.target.name.match(/^([^\[]+)\[(\d+)\]$/);
                if (!startMatch) return;
                const startColumn = editableNames.indexOf(startMatch[1]);

                rows.forEach((rowText, rowIndex) => {
                    const cells = rowText.split('\t');
                    const targetRow = tbodyRows[startRowIndex + rowIndex];
                    if (!targetRow) return;

                    const rowInputs = getRowInputs(targetRow);
                    cells.forEach((cell, cellIndex) => {
                        const colIndex = (rowIndex === 0 ? startColumn : 0) + cellIndex;
                        if (colIndex < 0 || colIndex >= rowInputs.length) return;
                        const targetInput = rowInputs[colIndex];
                        if (targetInput) {
                            targetInput.value = cell.trim();
                        }
                    });
                });
            });
        });
    });
</script>
<?php elseif($selected_kelas): ?>
    <div class="form-container"><p>Tidak ada siswa di kelas ini.</p></div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>