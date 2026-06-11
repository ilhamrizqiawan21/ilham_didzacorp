<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');

// Hanya Ilham Rizqiawan, S.Pd. yang boleh akses
if ($_SESSION['nama'] != 'Ilham Rizqiawan, S.Pd.') {
    echo "<script>alert('Akses ditolak!'); window.location.href='dashboard.php';</script>";
    exit;
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Fungsi untuk mengecek apakah password masih default (NIS)
function is_password_default($conn, $user_id, $username) {
    $query = "SELECT password FROM user WHERE id = $user_id";
    $res = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($res);
    $default_hash = md5($username);
    return ($row['password'] == $default_hash);
}

// ========== PROSES RESET PASSWORD ==========
if (isset($_GET['reset_password'])) {
    $user_id = (int)$_GET['reset_password'];
    $query_user = mysqli_query($conn, "SELECT username, role FROM user WHERE id = $user_id");
    if ($user_data = mysqli_fetch_assoc($query_user)) {
        if ($user_data['role'] == 'siswa') {
            $nis = $user_data['username'];
            $new_password = md5($nis);
            mysqli_query($conn, "UPDATE user SET password = '$new_password' WHERE id = $user_id");
            echo "<script>alert('Password siswa berhasil direset ke NIS: $nis'); window.location.href='user_management.php';</script>";
        } else {
            echo "<script>alert('Reset password hanya untuk akun siswa!'); window.location.href='user_management.php';</script>";
        }
    } else {
        echo "<script>alert('User tidak ditemukan!'); window.location.href='user_management.php';</script>";
    }
    exit;
}

// ========== PROSES EXPORT EXCEL ==========
if (isset($_GET['export_excel'])) {
    error_reporting(0);
    
    $query = "SELECT u.id, u.username, u.nama_lengkap, u.role, 
                     s.id as siswa_id, s.kelas_id, k.nama_kelas
              FROM user u
              LEFT JOIN siswa s ON u.id = s.user_id
              LEFT JOIN kelas k ON s.kelas_id = k.id
              ORDER BY (u.role = 'admin') DESC,
                       CASE WHEN (s.kelas_id IS NULL OR s.kelas_id = 0) THEN 1 ELSE 0 END,
                       k.nama_kelas ASC,
                       u.nama_lengkap ASC";
    $users = mysqli_query($conn, $query);
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('User Management');
    
    $sheet->setCellValue('A1', 'ID');
    $sheet->setCellValue('B1', 'Username');
    $sheet->setCellValue('C1', 'Nama Lengkap');
    $sheet->setCellValue('D1', 'Role');
    $sheet->setCellValue('E1', 'Kelas');
    $sheet->setCellValue('F1', 'Status Password');
    
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ];
    $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
    
    $row = 2;
    while ($user = mysqli_fetch_assoc($users)) {
        $sheet->setCellValue('A' . $row, $user['id']);
        $sheet->setCellValue('B' . $row, $user['username']);
        $sheet->setCellValue('C' . $row, $user['nama_lengkap']);
        $sheet->setCellValue('D' . $row, $user['role'] == 'admin' ? 'Guru' : 'Siswa');
        $sheet->setCellValue('E' . $row, $user['role'] == 'siswa' ? ($user['nama_kelas'] ?? '-') : '-');
        $status = '-';
        if ($user['role'] == 'siswa') {
            $default = is_password_default($conn, $user['id'], $user['username']);
            $status = $default ? 'Default (NIS)' : 'Sudah Diubah';
        }
        $sheet->setCellValue('F' . $row, $status);
        $row++;
    }
    
    foreach (range('A', 'F') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
    
    $lastRow = $row - 1;
    if ($lastRow >= 2) {
        $sheet->getStyle('A1:F' . $lastRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
    }
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="user_management.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

$title = 'User Management';
include '../includes/header.php';

$query = "SELECT u.id, u.username, u.nama_lengkap, u.role, 
                 s.id as siswa_id, s.kelas_id, k.nama_kelas
          FROM user u
          LEFT JOIN siswa s ON u.id = s.user_id
          LEFT JOIN kelas k ON s.kelas_id = k.id
          ORDER BY (u.role = 'admin') DESC,
                   CASE WHEN (s.kelas_id IS NULL OR s.kelas_id = 0) THEN 1 ELSE 0 END,
                   k.nama_kelas ASC,
                   u.nama_lengkap ASC";
$users = mysqli_query($conn, $query);
?>

<style>
.badge-default {
    background-color: #d1fae5;
    color: #065f46;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    display: inline-block;
}
.badge-changed {
    background-color: #fed7aa;
    color: #9b3412;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    display: inline-block;
}
.btn-reset-row {
    background-color: #fef3c7;
    color: #b45309;
    border: 1px solid #fde68a;
    padding: 6px 12px;
    font-size: 0.75rem;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.btn-reset-row:hover {
    background-color: #f59e0b;
    color: white;
    border-color: #f59e0b;
}
.btn-export {
    background-color: #10b981;
    color: white;
    border: none;
}
.btn-export:hover {
    background-color: #059669;
}
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-users-gear"></i> User Management</h2>
    <p class="page-subtitle">Kelola password akun. Untuk mengubah data siswa (nama, NIS, kelas), gunakan menu <strong>Kelas & Siswa</strong>.</p>
</div>

<div class="form-container">
    <div style="margin-bottom: 15px; text-align: right;">
        <a href="?export_excel=1" class="btn btn-export"><i class="fas fa-file-excel"></i> Export ke Excel</a>
    </div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Nama Lengkap</th>
                    <th>Role</th>
                    <th>Kelas</th>
                    <th>Status Password</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = mysqli_fetch_assoc($users)): 
                    $status_badge = '';
                    if ($user['role'] == 'siswa') {
                        if (is_password_default($conn, $user['id'], $user['username'])) {
                            $status_badge = '<span class="badge-default"><i class="fas fa-check-circle"></i> Default (NIS)</span>';
                        } else {
                            $status_badge = '<span class="badge-changed"><i class="fas fa-edit"></i> Sudah Diubah</span>';
                        }
                    } else {
                        $status_badge = '<span class="badge-default">-</span>';
                    }
                ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                    <td><?= $user['role'] == 'admin' ? 'Guru' : 'Siswa' ?></td>
                    <td><?= $user['role'] == 'siswa' ? htmlspecialchars($user['nama_kelas'] ?? '-') : '-' ?></td>
                    <td><?= $status_badge ?></td>
                    <td>
                        <?php if ($user['role'] == 'siswa'): ?>
                            <a href="?reset_password=<?= $user['id'] ?>" class="btn-reset-row" onclick="return confirm('Reset password untuk <?= htmlspecialchars($user['nama_lengkap']) ?> ke NIS (<?= htmlspecialchars($user['username']) ?>)?')">
                                <i class="fas fa-sync-alt"></i> Reset ke NIS
                            </a>
                        <?php else: ?>
                            <span style="color: #9ca3af; font-size: 0.7rem;">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>