<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Kelola Modul Ajar';
include '../includes/header.php';

// Ambil daftar bab untuk dropdown
$bab_list = mysqli_query($conn, "SELECT id, judul_bab FROM bab ORDER BY id");

// Simpan atau update modul ajar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan'])) {
    $id = (int)$_POST['id'];
    $bab_id = (int)$_POST['bab_id'];
    $identitas_madrasah = mysqli_real_escape_string($conn, $_POST['identitas_madrasah']);
    $nama_penyusun = mysqli_real_escape_string($conn, $_POST['nama_penyusun']);
    $nuptk = mysqli_real_escape_string($conn, $_POST['nuptk']);
    $mapel = mysqli_real_escape_string($conn, $_POST['mapel']);
    $kelas_semester = mysqli_real_escape_string($conn, $_POST['kelas_semester']);
    $alokasi_waktu_total = mysqli_real_escape_string($conn, $_POST['alokasi_waktu_total']);
    $tahun_pelajaran = mysqli_real_escape_string($conn, $_POST['tahun_pelajaran']);
    $pengetahuan_awal = mysqli_real_escape_string($conn, $_POST['pengetahuan_awal']);
    $minat = mysqli_real_escape_string($conn, $_POST['minat']);
    $latar_belakang = mysqli_real_escape_string($conn, $_POST['latar_belakang']);
    $kebutuhan_belajar = mysqli_real_escape_string($conn, $_POST['kebutuhan_belajar']);
    $topik_panca_cinta = mysqli_real_escape_string($conn, $_POST['topik_panca_cinta']);
    $materi_insersi = mysqli_real_escape_string($conn, $_POST['materi_insersi']);
    $jenis_pengetahuan = mysqli_real_escape_string($conn, $_POST['jenis_pengetahuan']);
    $relevansi = mysqli_real_escape_string($conn, $_POST['relevansi']);
    $tingkat_kesulitan = mysqli_real_escape_string($conn, $_POST['tingkat_kesulitan']);
    $struktur_materi = mysqli_real_escape_string($conn, $_POST['struktur_materi']);
    $integrasi_nilai = mysqli_real_escape_string($conn, $_POST['integrasi_nilai']);
    $profil_keimanan = mysqli_real_escape_string($conn, $_POST['profil_keimanan']);
    $profil_kewargaan = mysqli_real_escape_string($conn, $_POST['profil_kewargaan']);
    $profil_nalar_kritis = mysqli_real_escape_string($conn, $_POST['profil_nalar_kritis']);
    $profil_kreativitas = mysqli_real_escape_string($conn, $_POST['profil_kreativitas']);
    $profil_kolaborasi = mysqli_real_escape_string($conn, $_POST['profil_kolaborasi']);
    $profil_kemandirian = mysqli_real_escape_string($conn, $_POST['profil_kemandirian']);
    $profil_kesehatan = mysqli_real_escape_string($conn, $_POST['profil_kesehatan']);
    $profil_komunikasi = mysqli_real_escape_string($conn, $_POST['profil_komunikasi']);
    $cp_tajwid = mysqli_real_escape_string($conn, $_POST['cp_tajwid']);
    $lintas_displin = mysqli_real_escape_string($conn, $_POST['lintas_displin']);
    $tujuan_pembelajaran_1 = mysqli_real_escape_string($conn, $_POST['tujuan_pembelajaran_1']);
    $tujuan_pembelajaran_2 = mysqli_real_escape_string($conn, $_POST['tujuan_pembelajaran_2']);
    $tujuan_pembelajaran_3 = mysqli_real_escape_string($conn, $_POST['tujuan_pembelajaran_3']);
    $tujuan_pembelajaran_4 = mysqli_real_escape_string($conn, $_POST['tujuan_pembelajaran_4']);
    $indikator = mysqli_real_escape_string($conn, $_POST['indikator']);
    $iklim_budaya = mysqli_real_escape_string($conn, $_POST['iklim_budaya']);
    $topik_kontekstual = mysqli_real_escape_string($conn, $_POST['topik_kontekstual']);
    $model_pembelajaran = mysqli_real_escape_string($conn, $_POST['model_pembelajaran']);
    $pendekatan = mysqli_real_escape_string($conn, $_POST['pendekatan']);
    $metode = mysqli_real_escape_string($conn, $_POST['metode']);
    $strategi_diferensiasi = mysqli_real_escape_string($conn, $_POST['strategi_diferensiasi']);
    $kemitraan_sekolah = mysqli_real_escape_string($conn, $_POST['kemitraan_sekolah']);
    $kemitraan_masyarakat = mysqli_real_escape_string($conn, $_POST['kemitraan_masyarakat']);
    $mitra_digital = mysqli_real_escape_string($conn, $_POST['mitra_digital']);
    $lingkungan_fisik = mysqli_real_escape_string($conn, $_POST['lingkungan_fisik']);
    $lingkungan_virtual = mysqli_real_escape_string($conn, $_POST['lingkungan_virtual']);
    $budaya_belajar = mysqli_real_escape_string($conn, $_POST['budaya_belajar']);
    $pemanfaatan_digital = mysqli_real_escape_string($conn, $_POST['pemanfaatan_digital']);
    $langkah_pertemuan_1 = mysqli_real_escape_string($conn, $_POST['langkah_pertemuan_1']);
    $langkah_pertemuan_2 = mysqli_real_escape_string($conn, $_POST['langkah_pertemuan_2']);
    $langkah_pertemuan_3 = mysqli_real_escape_string($conn, $_POST['langkah_pertemuan_3']);
    $langkah_pertemuan_4 = mysqli_real_escape_string($conn, $_POST['langkah_pertemuan_4']);
    $asesmen_diagnostik = mysqli_real_escape_string($conn, $_POST['asesmen_diagnostik']);
    $asesmen_formatif = mysqli_real_escape_string($conn, $_POST['asesmen_formatif']);
    $asesmen_sumatif = mysqli_real_escape_string($conn, $_POST['asesmen_sumatif']);
    
    if ($id == 0) {
        $query = "INSERT INTO modul_ajar (bab_id, identitas_madrasah, nama_penyusun, nuptk, mapel, kelas_semester, alokasi_waktu_total, tahun_pelajaran, pengetahuan_awal, minat, latar_belakang, kebutuhan_belajar, topik_panca_cinta, materi_insersi, jenis_pengetahuan, relevansi, tingkat_kesulitan, struktur_materi, integrasi_nilai, profil_keimanan, profil_kewargaan, profil_nalar_kritis, profil_kreativitas, profil_kolaborasi, profil_kemandirian, profil_kesehatan, profil_komunikasi, cp_tajwid, lintas_displin, tujuan_pembelajaran_1, tujuan_pembelajaran_2, tujuan_pembelajaran_3, tujuan_pembelajaran_4, indikator, iklim_budaya, topik_kontekstual, model_pembelajaran, pendekatan, metode, strategi_diferensiasi, kemitraan_sekolah, kemitraan_masyarakat, mitra_digital, lingkungan_fisik, lingkungan_virtual, budaya_belajar, pemanfaatan_digital, langkah_pertemuan_1, langkah_pertemuan_2, langkah_pertemuan_3, langkah_pertemuan_4, asesmen_diagnostik, asesmen_formatif, asesmen_sumatif) 
                  VALUES ($bab_id, '$identitas_madrasah', '$nama_penyusun', '$nuptk', '$mapel', '$kelas_semester', '$alokasi_waktu_total', '$tahun_pelajaran', '$pengetahuan_awal', '$minat', '$latar_belakang', '$kebutuhan_belajar', '$topik_panca_cinta', '$materi_insersi', '$jenis_pengetahuan', '$relevansi', '$tingkat_kesulitan', '$struktur_materi', '$integrasi_nilai', '$profil_keimanan', '$profil_kewargaan', '$profil_nalar_kritis', '$profil_kreativitas', '$profil_kolaborasi', '$profil_kemandirian', '$profil_kesehatan', '$profil_komunikasi', '$cp_tajwid', '$lintas_displin', '$tujuan_pembelajaran_1', '$tujuan_pembelajaran_2', '$tujuan_pembelajaran_3', '$tujuan_pembelajaran_4', '$indikator', '$iklim_budaya', '$topik_kontekstual', '$model_pembelajaran', '$pendekatan', '$metode', '$strategi_diferensiasi', '$kemitraan_sekolah', '$kemitraan_masyarakat', '$mitra_digital', '$lingkungan_fisik', '$lingkungan_virtual', '$budaya_belajar', '$pemanfaatan_digital', '$langkah_pertemuan_1', '$langkah_pertemuan_2', '$langkah_pertemuan_3', '$langkah_pertemuan_4', '$asesmen_diagnostik', '$asesmen_formatif', '$asesmen_sumatif')";
    } else {
        $query = "UPDATE modul_ajar SET bab_id=$bab_id, identitas_madrasah='$identitas_madrasah', nama_penyusun='$nama_penyusun', nuptk='$nuptk', mapel='$mapel', kelas_semester='$kelas_semester', alokasi_waktu_total='$alokasi_waktu_total', tahun_pelajaran='$tahun_pelajaran', pengetahuan_awal='$pengetahuan_awal', minat='$minat', latar_belakang='$latar_belakang', kebutuhan_belajar='$kebutuhan_belajar', topik_panca_cinta='$topik_panca_cinta', materi_insersi='$materi_insersi', jenis_pengetahuan='$jenis_pengetahuan', relevansi='$relevansi', tingkat_kesulitan='$tingkat_kesulitan', struktur_materi='$struktur_materi', integrasi_nilai='$integrasi_nilai', profil_keimanan='$profil_keimanan', profil_kewargaan='$profil_kewargaan', profil_nalar_kritis='$profil_nalar_kritis', profil_kreativitas='$profil_kreativitas', profil_kolaborasi='$profil_kolaborasi', profil_kemandirian='$profil_kemandirian', profil_kesehatan='$profil_kesehatan', profil_komunikasi='$profil_komunikasi', cp_tajwid='$cp_tajwid', lintas_displin='$lintas_displin', tujuan_pembelajaran_1='$tujuan_pembelajaran_1', tujuan_pembelajaran_2='$tujuan_pembelajaran_2', tujuan_pembelajaran_3='$tujuan_pembelajaran_3', tujuan_pembelajaran_4='$tujuan_pembelajaran_4', indikator='$indikator', iklim_budaya='$iklim_budaya', topik_kontekstual='$topik_kontekstual', model_pembelajaran='$model_pembelajaran', pendekatan='$pendekatan', metode='$metode', strategi_diferensiasi='$strategi_diferensiasi', kemitraan_sekolah='$kemitraan_sekolah', kemitraan_masyarakat='$kemitraan_masyarakat', mitra_digital='$mitra_digital', lingkungan_fisik='$lingkungan_fisik', lingkungan_virtual='$lingkungan_virtual', budaya_belajar='$budaya_belajar', pemanfaatan_digital='$pemanfaatan_digital', langkah_pertemuan_1='$langkah_pertemuan_1', langkah_pertemuan_2='$langkah_pertemuan_2', langkah_pertemuan_3='$langkah_pertemuan_3', langkah_pertemuan_4='$langkah_pertemuan_4', asesmen_diagnostik='$asesmen_diagnostik', asesmen_formatif='$asesmen_formatif', asesmen_sumatif='$asesmen_sumatif' WHERE id=$id";
    }
    mysqli_query($conn, $query);
    echo "<script>alert('Modul ajar berhasil disimpan'); window.location.href='modul_ajar';</script>";
}

// Hapus modul ajar
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM modul_ajar WHERE id=$id");
    echo "<script>alert('Modul ajar dihapus'); window.location.href='modul_ajar';</script>";
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $q_edit = mysqli_query($conn, "SELECT * FROM modul_ajar WHERE id=$id_edit");
    $edit_data = mysqli_fetch_assoc($q_edit);
}
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-chalkboard"></i> Modul Ajar</h2>
    <p class="page-subtitle">Input lengkap modul ajar.</p>
</div>

<div class="form-container">
    <div class="form-title"><i class="fas fa-<?= $edit_data ? 'edit' : 'plus-circle' ?>"></i> <?= $edit_data ? 'Edit Modul Ajar' : 'Tambah Modul Ajar Baru' ?></div>
    <form method="POST">
        <input type="hidden" name="id" value="<?= $edit_data['id'] ?? 0 ?>">
        
        <!-- Identitas -->
        <div style="background: var(--gray-50); padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
            <h3><i class="fas fa-school"></i> Identitas Modul</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>Bab</label>
                    <select name="bab_id" class="form-select" required>
                        <option value="">-- Pilih Bab --</option>
                        <?php while($b = mysqli_fetch_assoc($bab_list)): ?>
                            <option value="<?= $b['id'] ?>" <?= ($edit_data['bab_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['judul_bab']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group"><label>Madrasah</label><input type="text" name="identitas_madrasah" class="form-input" value="<?= htmlspecialchars($edit_data['identitas_madrasah'] ?? 'MTs. Al-Ihsan Batujajar') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Nama Penyusun</label><input type="text" name="nama_penyusun" class="form-input" value="<?= htmlspecialchars($edit_data['nama_penyusun'] ?? 'ILHAM RIZQIAWAN, S.Pd.') ?>"></div>
                <div class="form-group"><label>NUPTK</label><input type="text" name="nuptk" class="form-input" value="<?= htmlspecialchars($edit_data['nuptk'] ?? '') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Mapel</label><input type="text" name="mapel" class="form-input" value="<?= htmlspecialchars($edit_data['mapel'] ?? 'Al-Qur\'an Hadis') ?>"></div>
                <div class="form-group"><label>Kelas/Semester</label><input type="text" name="kelas_semester" class="form-input" value="<?= htmlspecialchars($edit_data['kelas_semester'] ?? 'VIII / Ganjil') ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Alokasi Waktu Total</label><input type="text" name="alokasi_waktu_total" class="form-input" value="<?= htmlspecialchars($edit_data['alokasi_waktu_total'] ?? '8 JP') ?>"></div>
                <div class="form-group"><label>Tahun Pelajaran</label><input type="text" name="tahun_pelajaran" class="form-input" value="<?= htmlspecialchars($edit_data['tahun_pelajaran'] ?? '2025/2026') ?>"></div>
            </div>
        </div>
        
        <!-- Peserta Didik -->
        <h3><i class="fas fa-users"></i> Profil Peserta Didik</h3>
        <div class="form-group"><label>Pengetahuan Awal</label><textarea name="pengetahuan_awal" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['pengetahuan_awal'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Minat</label><textarea name="minat" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['minat'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Latar Belakang</label><textarea name="latar_belakang" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['latar_belakang'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Kebutuhan Belajar</label><textarea name="kebutuhan_belajar" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['kebutuhan_belajar'] ?? '') ?></textarea></div>
        
        <!-- Topik & Materi -->
        <h3><i class="fas fa-hand-sparkles"></i> Topik & Materi</h3>
        <div class="form-group"><label>Topik Panca Cinta</label><input type="text" name="topik_panca_cinta" class="form-input" value="<?= htmlspecialchars($edit_data['topik_panca_cinta'] ?? '') ?>"></div>
        <div class="form-group"><label>Materi Insersi</label><textarea name="materi_insersi" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['materi_insersi'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Jenis Pengetahuan</label><textarea name="jenis_pengetahuan" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['jenis_pengetahuan'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Relevansi</label><textarea name="relevansi" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['relevansi'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Tingkat Kesulitan</label><input type="text" name="tingkat_kesulitan" class="form-input" value="<?= htmlspecialchars($edit_data['tingkat_kesulitan'] ?? 'Sedang') ?>"></div>
        <div class="form-group"><label>Struktur Materi</label><textarea name="struktur_materi" class="form-textarea" rows="3"><?= htmlspecialchars($edit_data['struktur_materi'] ?? '') ?></textarea></div>
        
        <!-- Integrasi Nilai & Profil -->
        <h3><i class="fas fa-star-of-life"></i> Integrasi Nilai & Profil</h3>
        <div class="form-group"><label>Integrasi Nilai</label><textarea name="integrasi_nilai" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['integrasi_nilai'] ?? '') ?></textarea></div>
        <div class="form-row">
            <div class="form-group"><label>Profil Keimanan</label><input type="text" name="profil_keimanan" class="form-input" value="<?= htmlspecialchars($edit_data['profil_keimanan'] ?? '') ?>"></div>
            <div class="form-group"><label>Profil Kewargaan</label><input type="text" name="profil_kewargaan" class="form-input" value="<?= htmlspecialchars($edit_data['profil_kewargaan'] ?? '') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Profil Nalar Kritis</label><input type="text" name="profil_nalar_kritis" class="form-input" value="<?= htmlspecialchars($edit_data['profil_nalar_kritis'] ?? '') ?>"></div>
            <div class="form-group"><label>Profil Kreativitas</label><input type="text" name="profil_kreativitas" class="form-input" value="<?= htmlspecialchars($edit_data['profil_kreativitas'] ?? '') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Profil Kolaborasi</label><input type="text" name="profil_kolaborasi" class="form-input" value="<?= htmlspecialchars($edit_data['profil_kolaborasi'] ?? '') ?>"></div>
            <div class="form-group"><label>Profil Kemandirian</label><input type="text" name="profil_kemandirian" class="form-input" value="<?= htmlspecialchars($edit_data['profil_kemandirian'] ?? '') ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Profil Kesehatan</label><input type="text" name="profil_kesehatan" class="form-input" value="<?= htmlspecialchars($edit_data['profil_kesehatan'] ?? '') ?>"></div>
            <div class="form-group"><label>Profil Komunikasi</label><input type="text" name="profil_komunikasi" class="form-input" value="<?= htmlspecialchars($edit_data['profil_komunikasi'] ?? '') ?>"></div>
        </div>
        
        <!-- Capaian & Tujuan -->
        <h3><i class="fas fa-graduation-cap"></i> Capaian & Tujuan</h3>
        <div class="form-group"><label>CP Tajwid</label><textarea name="cp_tajwid" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['cp_tajwid'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Lintas Disiplin</label><textarea name="lintas_displin" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['lintas_displin'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Tujuan Pembelajaran 1</label><textarea name="tujuan_pembelajaran_1" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['tujuan_pembelajaran_1'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Tujuan Pembelajaran 2</label><textarea name="tujuan_pembelajaran_2" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['tujuan_pembelajaran_2'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Tujuan Pembelajaran 3</label><textarea name="tujuan_pembelajaran_3" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['tujuan_pembelajaran_3'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Tujuan Pembelajaran 4</label><textarea name="tujuan_pembelajaran_4" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['tujuan_pembelajaran_4'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Indikator</label><textarea name="indikator" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['indikator'] ?? '') ?></textarea></div>
        
        <!-- Strategi -->
        <h3><i class="fas fa-chalkboard-user"></i> Strategi & Model</h3>
        <div class="form-group"><label>Iklim Budaya</label><textarea name="iklim_budaya" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['iklim_budaya'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Topik Kontekstual</label><textarea name="topik_kontekstual" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['topik_kontekstual'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Model Pembelajaran</label><input type="text" name="model_pembelajaran" class="form-input" value="<?= htmlspecialchars($edit_data['model_pembelajaran'] ?? 'PBL') ?>"></div>
        <div class="form-group"><label>Pendekatan</label><input type="text" name="pendekatan" class="form-input" value="<?= htmlspecialchars($edit_data['pendekatan'] ?? 'Saintifik') ?>"></div>
        <div class="form-group"><label>Metode</label><input type="text" name="metode" class="form-input" value="<?= htmlspecialchars($edit_data['metode'] ?? 'Diskusi, Tanya Jawab') ?>"></div>
        <div class="form-group"><label>Strategi Diferensiasi</label><textarea name="strategi_diferensiasi" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['strategi_diferensiasi'] ?? '') ?></textarea></div>
        
        <!-- Kemitraan & Lingkungan -->
        <h3><i class="fas fa-handshake"></i> Kemitraan & Lingkungan</h3>
        <div class="form-group"><label>Kemitraan Sekolah</label><textarea name="kemitraan_sekolah" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['kemitraan_sekolah'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Kemitraan Masyarakat</label><textarea name="kemitraan_masyarakat" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['kemitraan_masyarakat'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Mitra Digital</label><textarea name="mitra_digital" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['mitra_digital'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Lingkungan Fisik</label><textarea name="lingkungan_fisik" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['lingkungan_fisik'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Lingkungan Virtual</label><textarea name="lingkungan_virtual" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['lingkungan_virtual'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Budaya Belajar</label><textarea name="budaya_belajar" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['budaya_belajar'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Pemanfaatan Digital</label><textarea name="pemanfaatan_digital" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['pemanfaatan_digital'] ?? '') ?></textarea></div>
        
        <!-- Langkah Pembelajaran -->
        <h3><i class="fas fa-tasks"></i> Langkah Pembelajaran (4 Pertemuan)</h3>
        <div class="form-group"><label>Pertemuan 1</label><textarea name="langkah_pertemuan_1" class="form-textarea" rows="3"><?= htmlspecialchars($edit_data['langkah_pertemuan_1'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Pertemuan 2</label><textarea name="langkah_pertemuan_2" class="form-textarea" rows="3"><?= htmlspecialchars($edit_data['langkah_pertemuan_2'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Pertemuan 3</label><textarea name="langkah_pertemuan_3" class="form-textarea" rows="3"><?= htmlspecialchars($edit_data['langkah_pertemuan_3'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Pertemuan 4</label><textarea name="langkah_pertemuan_4" class="form-textarea" rows="3"><?= htmlspecialchars($edit_data['langkah_pertemuan_4'] ?? '') ?></textarea></div>
        
        <!-- Asesmen -->
        <h3><i class="fas fa-clipboard-list"></i> Asesmen</h3>
        <div class="form-group"><label>Asesmen Diagnostik</label><textarea name="asesmen_diagnostik" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['asesmen_diagnostik'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Asesmen Formatif</label><textarea name="asesmen_formatif" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['asesmen_formatif'] ?? '') ?></textarea></div>
        <div class="form-group"><label>Asesmen Sumatif</label><textarea name="asesmen_sumatif" class="form-textarea" rows="2"><?= htmlspecialchars($edit_data['asesmen_sumatif'] ?? '') ?></textarea></div>
        
        <div class="btn-group">
            <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Modul Ajar</button>
            <a href="modul_ajar" class="btn btn-outline">Batal</a>
        </div>
    </form>
</div>

<!-- Daftar Modul Ajar -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-list"></i> Daftar Modul Ajar</div>
    <div class="table-wrapper">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Bab</th>
                    <th>Penyusun</th>
                    <th>Tahun</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $list = mysqli_query($conn, "SELECT m.id, b.judul_bab, m.nama_penyusun, m.tahun_pelajaran 
                    FROM modul_ajar m LEFT JOIN bab b ON m.bab_id = b.id ORDER BY m.id");
                while($row = mysqli_fetch_assoc($list)): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><strong><?= htmlspecialchars($row['judul_bab']) ?></strong></td>
                    <td><?= htmlspecialchars($row['nama_penyusun']) ?></td>
                    <td><?= htmlspecialchars($row['tahun_pelajaran']) ?></td>
                    <td>
                        <a href="?edit=<?= $row['id'] ?>" class="btn btn-sm"><i class="fas fa-edit"></i> Edit</a>
                        <a href="?hapus=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus modul ajar ini?')"><i class="fas fa-trash"></i> Hapus</a>
                        <a href="export_modul_ajar_excel?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-file-excel"></i> Excel</a>
                        <a href="export_modul_ajar_html?id=<?= $row['id'] ?>" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-print"></i> Cetak</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>