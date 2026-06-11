<?php
// Fungsi untuk format tanggal Indonesia
function tgl_indonesia($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $tgl = date('j', strtotime($tanggal));
    $bln = $bulan[(int) date('n', strtotime($tanggal))];
    $thn = date('Y', strtotime($tanggal));
    return "$tgl $bln $thn";
}

// Fungsi untuk mendapatkan nama hari
function hari_ini($tanggal) {
    $hari = date('N', strtotime($tanggal));
    $nama_hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    return $nama_hari[$hari - 1];
}

// Fungsi untuk menghasilkan pilihan bulan (untuk dropdown)
function bulan_options($selected = null) {
    $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $html = '';
    for ($i = 1; $i <= 12; $i++) {
        $val = date('Y-m', strtotime(date('Y') . '-' . $i . '-01'));
        $sel = ($selected == $val) ? 'selected' : '';
        $html .= "<option value=\"$val\" $sel>{$bulan[$i-1]} " . date('Y') . "</option>";
    }
    return $html;
}

// Fungsi untuk mengecek apakah user sudah login, jika tidak redirect
function cek_login($role_required = null) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /index.php');
        exit;
    }
    if ($role_required && $_SESSION['role'] != $role_required) {
        header('Location: /index.php');
        exit;
    }
}

// Fungsi untuk membuat teks status absensi dengan badge modern (HANYA SATU VERSI)
function status_badge($status) {
    switch ($status) {
        case 'hadir': 
            return '<span style="color:#2ecc71; font-weight:600;">✓ Hadir</span>';
        case 'sakit': 
            return '<span style="color:#f39c12; font-weight:600;">🤒 Sakit</span>';
        case 'izin': 
            return '<span style="color:#3498db; font-weight:600;">📝 Izin</span>';
        case 'alpha': 
            return '<span style="color:#e74c3c; font-weight:600;">✗ Alpha</span>';
        default: 
            return '<span style="color:#999;">-</span>';
    }
}

// Fungsi untuk hitung total pertemuan dalam satu bulan (asumsi 4 minggu)
function total_pertemuan_bulan($tahun, $bulan) {
    // Anda bisa hitung dari database nanti, atau gunakan logika minggu
    // Sederhananya kita asumsikan 4 kali pertemuan per bulan
    return 4;
}

// Ambil nilai pengaturan
function get_pengaturan($conn, $key) {
    $result = mysqli_query($conn, "SELECT value FROM pengaturan WHERE `key` = '$key'");
    if ($result && mysqli_num_rows($result)) {
        $row = mysqli_fetch_assoc($result);
        return $row['value'];
    }
    return null;
}

// Simpan nilai pengaturan
function set_pengaturan($conn, $key, $value) {
    $value = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT INTO pengaturan (`key`, `value`) VALUES ('$key', '$value') ON DUPLICATE KEY UPDATE `value` = '$value'");
}

// Khusus untuk tahun ajaran dan semester aktif
function get_tahun_ajaran_aktif($conn) {
    return get_pengaturan($conn, 'tahun_ajaran_aktif');
}

function get_semester_aktif($conn) {
    return get_pengaturan($conn, 'semester_aktif');
}
// Tidak perlu fungsi lighten_color karena kita pakai opacity hex.
// Pastikan fungsi-fungsi berikut sudah ada:
// - get_pengaturan($conn, $key)
// - set_pengaturan($conn, $key, $value)
// - get_tahun_ajaran_aktif($conn)
// - get_semester_aktif($conn)
// - cek_login($role)

// Jika belum ada, berikut contoh minimal (sesuaikan dengan struktur tabel pengaturan Anda):

/*
function get_pengaturan($conn, $key) {
    $result = mysqli_query($conn, "SELECT value FROM pengaturan WHERE `key` = '$key'");
    if($row = mysqli_fetch_assoc($result)) return $row['value'];
    return null;
}

function set_pengaturan($conn, $key, $value) {
    $value = mysqli_real_escape_string($conn, $value);
    mysqli_query($conn, "INSERT INTO pengaturan (`key`, value) VALUES ('$key', '$value')
        ON DUPLICATE KEY UPDATE value = '$value'");
}

function get_tahun_ajaran_aktif($conn) {
    return get_pengaturan($conn, 'tahun_ajaran_aktif') ?: '2024/2025';
}

function get_semester_aktif($conn) {
    return get_pengaturan($conn, 'semester_aktif') ?: '1';
}
*/

?>