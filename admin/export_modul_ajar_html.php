<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');

// Ambil semua data modul ajar
$data = mysqli_query($conn, "SELECT * FROM modul_ajar ORDER BY id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Modul Ajar - Format Dokumen</title>
    <style>
        @media print {
            body { margin: 1.5cm; }
            .page-break { page-break-before: always; }
            .no-print { display: none; }
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            margin: 2cm;
            background: white;
            color: black;
        }
        .modul-dokumen {
            margin-bottom: 40px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 20px;
        }
        .modul-header {
            text-align: center;
            margin-bottom: 25px;
        }
        .modul-header h2 {
            margin: 0;
            font-size: 18pt;
            text-transform: uppercase;
        }
        .modul-header h3 {
            margin: 5px 0;
            font-size: 14pt;
            font-weight: normal;
        }
        .modul-header hr {
            width: 50%;
            margin: 10px auto;
            border: 1px solid #000;
        }
        .section-title {
            font-weight: bold;
            font-size: 14pt;
            margin: 20px 0 10px 0;
            background: #f0f0f0;
            padding: 5px 10px;
            border-left: 5px solid #10b981;
        }
        .subsection-title {
            font-weight: bold;
            font-size: 13pt;
            margin: 15px 0 8px 0;
            border-bottom: 1px solid #aaa;
        }
        .info-grid {
            display: flex;
            flex-wrap: wrap;
            margin: 10px 0;
        }
        .info-item {
            width: 50%;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 180px;
            display: inline-block;
        }
        .text-content {
            text-align: justify;
            margin: 8px 0;
        }
        .footer-doc {
            margin-top: 30px;
            text-align: right;
            font-size: 10pt;
        }
        .footer-doc table {
            width: 100%;
            margin-top: 20px;
        }
        .footer-doc td {
            width: 50%;
            vertical-align: top;
        }
    </style>
</head>
<body>

<?php 
$no_modul = 1;
while($d = mysqli_fetch_assoc($data)): 
?>
<div class="modul-dokumen">
    <!-- Header -->
    <div class="modul-header">
        <h2>MODUL AJAR</h2>
        <h3>KURIKULUM MERDEKA (KBC)</h3>
        <hr>
        <p>
            <strong>Nama Madrasah</strong> : <?= htmlspecialchars($d['identitas_madrasah'] ?? 'MTs. Al-Ihsan Batujajar') ?><br>
            <strong>Nama Penyusun</strong> : <?= htmlspecialchars($d['nama_penyusun'] ?? 'ILHAM RIZQIAWAN, S.Pd.') ?><br>
            <strong>NUPTK</strong> : <?= htmlspecialchars($d['nuptk'] ?? '7245759660200023') ?><br>
            <strong>Mata Pelajaran</strong> : <?= htmlspecialchars($d['mapel'] ?? 'Al-Qur\'an Hadis') ?><br>
            <strong>Kelas / Semester</strong> : <?= htmlspecialchars($d['kelas_semester'] ?? 'VIII / Ganjil & Genap') ?>
        </p>
    </div>

    <!-- Judul Bab -->
    <div class="section-title">
        BAB <?= htmlspecialchars($d['bab']) ?>
    </div>

    <!-- A. IDENTITAS MODUL -->
    <div class="section-title">A. IDENTITAS MODUL</div>
    <div class="info-grid">
        <div class="info-item"><span class="info-label">Nama Madrasah</span> : <?= htmlspecialchars($d['identitas_madrasah'] ?? 'MTs. Al-Ihsan Batujajar') ?></div>
        <div class="info-item"><span class="info-label">Nama Penyusun</span> : <?= htmlspecialchars($d['nama_penyusun'] ?? 'ILHAM RIZQIAWAN, S.Pd.') ?></div>
        <div class="info-item"><span class="info-label">Mata Pelajaran</span> : <?= htmlspecialchars($d['mapel'] ?? 'Al-Qur\'an Hadis') ?></div>
        <div class="info-item"><span class="info-label">Kelas / Fase / Semester</span> : <?= htmlspecialchars($d['kelas_semester'] ?? 'VIII / D / Ganjil') ?></div>
        <div class="info-item"><span class="info-label">Alokasi Waktu</span> : <?= $d['alokasi_waktu_total'] ?? '8 JP' ?></div>
        <div class="info-item"><span class="info-label">Tahun Pelajaran</span> : <?= $d['tahun_pelajaran'] ?? '2025/2026' ?></div>
    </div>

    <!-- B. IDENTIFIKASI KESIAPAN PESERTA DIDIK -->
    <div class="section-title">B. IDENTIFIKASI KESIAPAN PESERTA DIDIK</div>
    <div class="subsection-title">Pengetahuan Awal</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['pengetahuan_awal'] ?? '-')) ?></div>
    <div class="subsection-title">Minat</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['minat'] ?? '-')) ?></div>
    <div class="subsection-title">Latar Belakang</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['latar_belakang'] ?? '-')) ?></div>
    <div class="subsection-title">Kebutuhan Belajar</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['kebutuhan_belajar'] ?? '-')) ?></div>

    <!-- C. TEMA KURIKULUM BERBASIS CINTA (KBC) -->
    <div class="section-title">C. TEMA KURIKULUM BERBASIS CINTA (KBC)</div>
    <div class="subsection-title">Topik Panca Cinta</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['topik_panca_cinta'] ?? '-')) ?></div>
    <div class="subsection-title">Materi Insersi</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['materi_insersi'] ?? '-')) ?></div>

    <!-- D. KARAKTERISTIK MATERI PELAJARAN -->
    <div class="section-title">D. KARAKTERISTIK MATERI PELAJARAN</div>
    <div class="subsection-title">Jenis Pengetahuan</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['jenis_pengetahuan'] ?? '-')) ?></div>
    <div class="subsection-title">Relevansi</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['relevansi'] ?? '-')) ?></div>
    <div class="subsection-title">Tingkat Kesulitan</div>
    <div class="text-content"><?= htmlspecialchars($d['tingkat_kesulitan'] ?? '-') ?></div>
    <div class="subsection-title">Struktur Materi</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['struktur_materi'] ?? '-')) ?></div>
    <div class="subsection-title">Integrasi Nilai dan Karakter</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['integrasi_nilai'] ?? '-')) ?></div>

    <!-- E. DIMENSI PROFIL LULUSAN -->
    <div class="section-title">E. DIMENSI PROFIL LULUSAN</div>
    <div class="text-content">
        <strong>Keimanan dan Ketakwaan</strong> : <?= nl2br(htmlspecialchars($d['profil_keimanan'] ?? '-')) ?><br>
        <strong>Kewargaan</strong> : <?= nl2br(htmlspecialchars($d['profil_kewargaan'] ?? '-')) ?><br>
        <strong>Penalaran Kritis</strong> : <?= nl2br(htmlspecialchars($d['profil_nalar_kritis'] ?? '-')) ?><br>
        <strong>Kreativitas</strong> : <?= nl2br(htmlspecialchars($d['profil_kreativitas'] ?? '-')) ?><br>
        <strong>Kolaborasi</strong> : <?= nl2br(htmlspecialchars($d['profil_kolaborasi'] ?? '-')) ?><br>
        <strong>Kemandirian</strong> : <?= nl2br(htmlspecialchars($d['profil_kemandirian'] ?? '-')) ?><br>
        <strong>Kesehatan</strong> : <?= nl2br(htmlspecialchars($d['profil_kesehatan'] ?? '-')) ?><br>
        <strong>Komunikasi</strong> : <?= nl2br(htmlspecialchars($d['profil_komunikasi'] ?? '-')) ?>
    </div>

    <!-- CAPAIAN PEMBELAJARAN (CP) -->
    <div class="section-title">CAPAIAN PEMBELAJARAN (CP)</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['cp_tajwid'] ?? '-')) ?></div>

    <!-- LINTAS DISIPLIN -->
    <div class="section-title">LINTAS DISIPLIN ILMU</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['lintas_displin'] ?? '-')) ?></div>

    <!-- TUJUAN PEMBELAJARAN -->
    <div class="section-title">TUJUAN PEMBELAJARAN</div>
    <div class="text-content">
        <strong>Pertemuan 1</strong> : <?= nl2br(htmlspecialchars($d['tujuan_pembelajaran_1'] ?? '-')) ?><br>
        <strong>Pertemuan 2</strong> : <?= nl2br(htmlspecialchars($d['tujuan_pembelajaran_2'] ?? '-')) ?><br>
        <strong>Pertemuan 3</strong> : <?= nl2br(htmlspecialchars($d['tujuan_pembelajaran_3'] ?? '-')) ?><br>
        <strong>Pertemuan 4</strong> : <?= nl2br(htmlspecialchars($d['tujuan_pembelajaran_4'] ?? '-')) ?>
    </div>

    <!-- INDIKATOR -->
    <div class="section-title">INDIKATOR KETERCAPAIAN</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['indikator'] ?? '-')) ?></div>

    <!-- IKLIM/BUDAYA MADRASAH -->
    <div class="section-title">IKLIM/BUDAYA MADRASAH</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['iklim_budaya'] ?? '-')) ?></div>

    <!-- TOPIK KONTEKSTUAL -->
    <div class="section-title">TOPIK KONTEKSTUAL</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['topik_kontekstual'] ?? '-')) ?></div>

    <!-- KERANGKA PEMBELAJARAN -->
    <div class="section-title">KERANGKA PEMBELAJARAN</div>
    <div class="subsection-title">Model Pembelajaran</div>
    <div class="text-content"><?= htmlspecialchars($d['model_pembelajaran'] ?? '-') ?></div>
    <div class="subsection-title">Pendekatan</div>
    <div class="text-content"><?= htmlspecialchars($d['pendekatan'] ?? '-') ?></div>
    <div class="subsection-title">Metode</div>
    <div class="text-content"><?= htmlspecialchars($d['metode'] ?? '-') ?></div>
    <div class="subsection-title">Strategi Diferensiasi</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['strategi_diferensiasi'] ?? '-')) ?></div>
    <div class="subsection-title">Kemitraan Sekolah</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['kemitraan_sekolah'] ?? '-')) ?></div>
    <div class="subsection-title">Kemitraan Masyarakat</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['kemitraan_masyarakat'] ?? '-')) ?></div>
    <div class="subsection-title">Mitra Digital</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['mitra_digital'] ?? '-')) ?></div>
    <div class="subsection-title">Lingkungan Fisik</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['lingkungan_fisik'] ?? '-')) ?></div>
    <div class="subsection-title">Lingkungan Virtual</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['lingkungan_virtual'] ?? '-')) ?></div>
    <div class="subsection-title">Budaya Belajar</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['budaya_belajar'] ?? '-')) ?></div>
    <div class="subsection-title">Pemanfaatan Digital</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['pemanfaatan_digital'] ?? '-')) ?></div>

    <!-- LANGKAH PEMBELAJARAN -->
    <div class="section-title">LANGKAH PEMBELAJARAN</div>
    <div class="subsection-title">Pertemuan 1</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['langkah_pertemuan_1'] ?? '-')) ?></div>
    <div class="subsection-title">Pertemuan 2</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['langkah_pertemuan_2'] ?? '-')) ?></div>
    <div class="subsection-title">Pertemuan 3</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['langkah_pertemuan_3'] ?? '-')) ?></div>
    <div class="subsection-title">Pertemuan 4</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['langkah_pertemuan_4'] ?? '-')) ?></div>

    <!-- ASESMEN -->
    <div class="section-title">ASESMEN</div>
    <div class="subsection-title">Asesmen Diagnostik</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['asesmen_diagnostik'] ?? '-')) ?></div>
    <div class="subsection-title">Asesmen Formatif</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['asesmen_formatif'] ?? '-')) ?></div>
    <div class="subsection-title">Asesmen Sumatif</div>
    <div class="text-content"><?= nl2br(htmlspecialchars($d['asesmen_sumatif'] ?? '-')) ?></div>

    <!-- Footer tanda tangan -->
    <div class="footer-doc">
        <table>
            <tr>
                <td>Mengetahui,<br>Kepala Madrasah</td>
                <td>Bandung Barat, <?= date('d F Y') ?><br>Guru Mata Pelajaran</td>
            </tr>
            <tr>
                <td style="padding-top: 50px;">(____________________)</td>
                <td style="padding-top: 50px;"><?= htmlspecialchars($d['nama_penyusun'] ?? 'ILHAM RIZQIAWAN, S.Pd.') ?></td>
            </tr>
        </table>
    </div>

</div>

<?php if($no_modul++ < mysqli_num_rows($data)) echo '<div class="page-break"></div>'; ?>
<?php endwhile; ?>

<div class="no-print" style="text-align:center; margin-top:30px;">
    <button onclick="window.print()" style="padding:8px 16px;">🖨️ Cetak / Simpan PDF</button>
    <p style="margin-top:10px;">Tekan tombol di atas, lalu pilih "Save as PDF".</p>
</div>

</body>
</html>