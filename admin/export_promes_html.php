<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');

$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$where = $semester ? "WHERE semester='$semester'" : "";
$data = mysqli_query($conn, "SELECT * FROM promes $where ORDER BY FIELD(bulan,'Juli','Agustus','September','Oktober','November','Desember','Januari','Februari','Maret','April','Mei','Juni'), minggu_ke");
?>
<!DOCTYPE html>
<html>
<head>
    <title>PROMES - Program Semester</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        h2, h3 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #f0f0f0; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <h2>PROGRAM SEMESTER (PROMES)</h2>
    <h3>MTs. Al-Ihsan Batujajar<br>Mata Pelajaran: Al-Qur'an Hadis</h3>
    <?php if ($semester): ?>
        <p><strong>Semester:</strong> <?= $semester == 1 ? 'Ganjil' : 'Genap' ?></p>
    <?php endif; ?>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Bulan</th>
                <th>Minggu Ke-</th>
                <th>Topik / Materi</th>
                <th>Alokasi Waktu</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while($d = mysqli_fetch_assoc($data)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($d['bulan']) ?></td>
                <td><?= $d['minggu_ke'] ?></td>
                <td><?= htmlspecialchars($d['topik_materi']) ?></td>
                <td><?= $d['alokasi_waktu'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <div class="footer">
        Dicetak pada: <?= date('d-m-Y H:i:s') ?>
    </div>
    <div class="no-print" style="margin-top:20px; text-align:center;">
        <button onclick="window.print()">Cetak / Simpan PDF</button>
        <button onclick="window.close()">Tutup</button>
    </div>
</body>
</html>