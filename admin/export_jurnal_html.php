<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Export Jurnal Mengajar</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h2, h3 {
            text-align: center;
            margin: 0;
        }
        h2 {
            margin-top: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            vertical-align: top;
            text-align: left;
        }
        th {
            background: #f2f2f2;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
        }
        @media print {
            body {
                margin: 0;
                padding: 10px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>JURNAL MENGAJAR GURU</h2>
        <h3>MTs. Al-Ihsan Batujajar</h3>
        <p>Mata Pelajaran: Al-Qur'an Hadis</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Bab</th>
                <th>Alur Tujuan Pembelajaran</th>
                <th>Materi Pokok</th>
                <th>Jam Ke-</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $data = mysqli_query($conn, "SELECT * FROM jurnal ORDER BY hari_tanggal DESC");
            $no = 1;
            while ($row = mysqli_fetch_assoc($data)):
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['hari_tanggal']) ?></td>
                <td><?= htmlspecialchars($row['bab']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['alur_tujuan'])) ?></td>
                <td><?= htmlspecialchars($row['materi']) ?></td>
                <td><?= htmlspecialchars($row['jam_ke']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['keterangan'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: <?= date('d-m-Y H:i:s') ?>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print();">Cetak / Simpan sebagai PDF</button>
        <button onclick="window.close();">Tutup</button>
    </div>
</body>
</html>