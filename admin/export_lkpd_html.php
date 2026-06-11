<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');

$data = mysqli_query($conn, "SELECT * FROM lkpd ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>LKPD - Lembar Kerja Peserta Didik</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; margin: 20px; }
        h2, h3 { text-align: center; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; vertical-align: top; }
        th { background: #e9ecef; text-align: center; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; }
    </style>
</head>
<body>
    <h2>LEMBAR KERJA PESERTA DIDIK (LKPD)</h2>
    <h3>MTs. Al-Ihsan Batujajar<br>Mata Pelajaran: Al-Qur'an Hadis</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Bab</th>
                <th>Judul</th>
                <th>Petunjuk Belajar</th>
                <th>Tujuan Pembelajaran</th>
                <th>Materi Pokok</th>
                <th>Tugas / Langkah Kerja</th>
                <th>Rubrik Penilaian</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; while($d = mysqli_fetch_assoc($data)): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($d['bab']) ?></td>
                <td><?= htmlspecialchars($d['judul']) ?></td>
                <td><?= nl2br(htmlspecialchars($d['petunjuk_belajar'])) ?></td>
                <td><?= nl2br(htmlspecialchars($d['tujuan_pembelajaran'])) ?></td>
                <td><?= nl2br(htmlspecialchars($d['materi_pokok'])) ?></td>
                <td><?= nl2br(htmlspecialchars($d['tugas'])) ?></td>
                <td><?= nl2br(htmlspecialchars($d['rubrik'])) ?></td>
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