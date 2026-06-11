<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$where = $semester ? "WHERE semester='$semester'" : "";
$data = mysqli_query($conn, "SELECT * FROM atp $where ORDER BY semester, id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>ATP - Alur Tujuan Pembelajaran</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        h2, h3 { text-align: center; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f2f2f2; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; }
    </style>
</head>
<body>
    <h2>ALUR TUJUAN PEMBELAJARAN (ATP)</h2>
    <h3>MTs. Al-Ihsan Batujajar<br>Mata Pelajaran: Al-Qur'an Hadis</h3>
    <?php if ($semester): ?>
        <p><strong>Semester:</strong> <?= $semester == 1 ? 'Ganjil' : 'Genap' ?></p>
    <?php endif; ?>
    <table>
        <thead>
            <tr><th>No</th><th>Bab</th><th>Alur Tujuan</th><th>Materi</th><th>Waktu</th><th>Semester</th></tr>
        </thead>
        <tbody>
            <?php $no=1; while($d=mysqli_fetch_assoc($data)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($d['bab']) ?></td>
                <td><?= nl2br(htmlspecialchars($d['alur_tujuan'])) ?></td>
                <td><?= htmlspecialchars($d['materi']) ?></td>
                <td><?= $d['alokasi_waktu'] ?></td>
                <td><?= $d['semester']==1?'Ganjil':'Genap' ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <div class="footer">Dicetak: <?= date('d-m-Y H:i:s') ?></div>
    <div class="no-print" style="margin-top:20px; text-align:center;">
        <button onclick="window.print()" style="padding:8px 16px;">Cetak / Simpan PDF</button>
        <button onclick="window.close()" style="padding:8px 16px;">Tutup</button>
    </div>
</body>
</html>