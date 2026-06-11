<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');

$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$where = $semester ? "WHERE semester='$semester'" : "";
$data = mysqli_query($conn, "SELECT * FROM kktp $where ORDER BY semester, id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>KKTP - Kriteria Ketercapaian Tujuan Pembelajaran</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 12px; margin: 20px; }
        h2, h3 { text-align: center; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #e9ecef; text-align: center; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; }
    </style>
</head>
<body>
    <h2>KRITERIA KETERCAPAIAN TUJUAN PEMBELAJARAN (KKTP)</h2>
    <h3>MTs. Al-Ihsan Batujajar<br>Mata Pelajaran: Al-Qur'an Hadis</h3>
    <?php if ($semester): ?>
        <p><strong>Semester:</strong> <?= $semester == 1 ? 'Ganjil' : 'Genap' ?></p>
    <?php endif; ?>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Bab</th>
                <th>Alur Tujuan Pembelajaran</th>
                <th>Skala 0-40%</th>
                <th>Skala 41-65%</th>
                <th>Skala 66-85%</th>
                <th>Skala 86-100%</th>
                <th>Semester</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while($d = mysqli_fetch_assoc($data)): 
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($d['bab']) ?></td>
                <td><?= nl2br(htmlspecialchars($d['alur_tujuan'])) ?></td>
                <td><?= nl2br(htmlspecialchars($d['skala_0_40'])) ?></td>
                <td><?= nl2br(htmlspecialchars($d['skala_41_65'])) ?></td>
                <td><?= nl2br(htmlspecialchars($d['skala_66_85'])) ?></td>
                <td><?= nl2br(htmlspecialchars($d['skala_86_100'])) ?></td>
                <td><?= $d['semester'] == 1 ? 'Ganjil' : 'Genap' ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <div class="footer">
        Dicetak pada: <?= date('d-m-Y H:i:s') ?> &nbsp;|&nbsp;
        <span class="no-print">Tekan Ctrl+P untuk mencetak atau simpan sebagai PDF.</span>
    </div>
</body>
</html>