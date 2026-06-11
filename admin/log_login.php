<?php
// Bersihkan semua buffer output agar header bisa dikirim dengan benar
if (ob_get_level()) ob_end_clean();
ob_start();

include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Riwayat Login Lengkap';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Filter tanggal dan role (sama seperti sebelumnya)
$start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($conn, $_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? mysqli_real_escape_string($conn, $_GET['end_date']) : '';
$filter_role = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';
$export = isset($_GET['export']) ? $_GET['export'] : '';

$where = [];
if (!empty($start_date)) $where[] = "DATE(login_time) >= '$start_date'";
if (!empty($end_date)) $where[] = "DATE(login_time) <= '$end_date'";
if (!empty($filter_role) && in_array($filter_role, ['admin', 'siswa'])) $where[] = "role = '$filter_role'";
$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// ========================
// PROSES EXPORT KE EXCEL
// ========================
if ($export === 'xlsx') {
    $query_export = "SELECT login_time, username, nama_lengkap, role, ip_address, user_agent 
                     FROM log_login $where_sql 
                     ORDER BY login_time DESC";
    $result_export = mysqli_query($conn, $query_export);

    // Buat Spreadsheet baru
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Riwayat Login');

    // Header tabel
    $headers = ['Waktu Login', 'Username', 'Nama Lengkap', 'Role', 'IP Address', 'User Agent'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '1', $header);
        $col++;
    }

    // Style header: bold, background biru muda, border
    $headerStyle = [
        'font' => ['bold' => true, 'size' => 11],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6F0FF']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
    ];
    $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

    // Isi data
    $row = 2;
    while ($data = mysqli_fetch_assoc($result_export)) {
        $sheet->setCellValue('A' . $row, date('d/m/Y H:i:s', strtotime($data['login_time'])));
        $sheet->setCellValue('B' . $row, $data['username']);
        $sheet->setCellValue('C' . $row, $data['nama_lengkap']);
        $sheet->setCellValue('D' . $row, ($data['role'] == 'admin' ? 'Guru' : 'Siswa'));
        $sheet->setCellValue('E' . $row, $data['ip_address']);
        $sheet->setCellValue('F' . $row, $data['user_agent']);
        $sheet->getStyle('A' . $row . ':F' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $row++;
    }

    // Auto-size kolom A sampai F
    foreach (range('A', 'F') as $c) $sheet->getColumnDimension($c)->setAutoSize(true);
    $sheet->getColumnDimension('F')->setWidth(60); // User Agent agak lebar

    // Bersihkan buffer dan kirim header download
    ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="riwayat_login_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ========================
// TAMPILAN WEB (PAGINATION)
// ========================
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$query_count = "SELECT COUNT(*) as total FROM log_login $where_sql";
$count_res = mysqli_query($conn, $query_count);
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $limit);

$query = "SELECT * FROM log_login $where_sql ORDER BY login_time DESC LIMIT $offset, $limit";
$logs = mysqli_query($conn, $query);

include '../includes/header.php';
?>

<style>
.filter-bar {
    background: #f8fafc;
    padding: 15px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}
.pagination {
    margin-top: 20px;
    text-align: center;
}
.pagination a {
    display: inline-block;
    padding: 6px 12px;
    margin: 0 2px;
    background: #f1f5f9;
    border-radius: 6px;
    text-decoration: none;
    color: #1e293b;
}
.pagination a.active {
    background: #4f46e5;
    color: white;
}
.btn-excel {
    background-color: #28a745;
    color: white;
    border: none;
    margin-left: 10px;
}
.btn-excel:hover {
    background-color: #218838;
}
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-history"></i> Riwayat Login</h2>
    <p class="page-subtitle">Semua aktivitas login guru dan siswa. Bisa difilter berdasarkan tanggal dan role.</p>
</div>

<div class="form-container">
    <form method="GET" class="filter-bar" id="filterForm">
        <div class="form-group">
            <label>Tanggal Mulai</label>
            <input type="date" name="start_date" class="form-input" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="form-group">
            <label>Tanggal Selesai</label>
            <input type="date" name="end_date" class="form-input" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" class="form-select">
                <option value="">-- Semua Role --</option>
                <option value="admin" <?= $filter_role == 'admin' ? 'selected' : '' ?>>Guru</option>
                <option value="siswa" <?= $filter_role == 'siswa' ? 'selected' : '' ?>>Siswa</option>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="log_login" class="btn btn-outline">Reset</a>
            <button type="button" id="exportExcelBtn" class="btn btn-excel">
                <i class="fas fa-file-excel"></i> Export ke Excel (XLSX)
            </button>
        </div>
    </form>

    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Waktu Login</th>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Role</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($logs) > 0): ?>
                    <?php while($log = mysqli_fetch_assoc($logs)): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i:s', strtotime($log['login_time'])) ?></td>
                        <td><?= htmlspecialchars($log['username']) ?></td>
                        <td><?= htmlspecialchars($log['nama_lengkap']) ?></td>
                        <td><?= $log['role'] == 'admin' ? 'Guru' : 'Siswa' ?></td>
                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                        <td style="max-width:250px; word-break:break-all;"><?= htmlspecialchars(substr($log['user_agent'], 0, 80)) ?>...</td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center;">Tidak ada data riwayat login</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&role=<?= urlencode($filter_role) ?>&page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('exportExcelBtn').addEventListener('click', function() {
    var form = document.getElementById('filterForm');
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'export';
    input.value = 'xlsx';
    form.appendChild(input);
    form.target = '_blank';
    form.submit();
    form.target = '';
    form.removeChild(input);
});
</script>

<?php include '../includes/footer.php'; ?>