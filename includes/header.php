<?php
if (!isset($_SESSION)) session_start();

// Jangan override $base_url, biar dari config.php
$role = $_SESSION['role'] ?? '';
$nama = $_SESSION['nama'] ?? 'Pengunjung';
// Load fungsi pendukung (untuk get_pengaturan)
require_once __DIR__ . '/fungsi.php';

// Pastikan koneksi database tersedia (dari config.php yang di-include sebelumnya)
if (!isset($conn) && isset($GLOBALS['conn'])) {
    $conn = $GLOBALS['conn'];
}

// Baca warna tema dari database
$warna_tema = get_pengaturan($conn, 'warna_tema');
if (!$warna_tema) $warna_tema = 'hijau';

// Definisikan nilai warna sesuai pilihan
switch ($warna_tema) {
    case 'biru-azure':
        $primary_600 = '#0078D7';
        $primary_700 = '#0063b1';
        $primary_800 = '#0050a0';
        break;
    case 'hijau-muda':
        $primary_600 = '#4ade80';
        $primary_700 = '#22c55e';
        $primary_800 = '#16a34a';
        break;
    case 'biru-aqua':
        $primary_600 = '#00b4d8';
        $primary_700 = '#0096c7';
        $primary_800 = '#0077b6';
        break;
    default: // hijau (default)
        $primary_600 = '#059669';
        $primary_700 = '#047857';
        $primary_800 = '#065f46';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?= $title ?? 'Sistem Pembelajaran Al-Qur\'an Hadis' ?></title>
    <link rel="icon" href="<?= $base_url ?>assets/images/favicon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?= $base_url ?>style.css">
    <style>
        :root {
            --primary-600: <?= $primary_600 ?>;
            --primary-700: <?= $primary_700 ?>;
            --primary-800: <?= $primary_800 ?>;
        }
        /* Perbaikan form-row mobile */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 0.8rem !important;
                overflow-x: hidden !important;
            }
            .form-container {
                padding: 0.8rem !important;
                margin-bottom: 1rem !important;
            }
            .stats-grid { gap: 0.8rem !important; }
            .table-wrapper {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
                margin: 0 -0.5rem !important;
                padding: 0 0.5rem !important;
                width: calc(100% + 1rem) !important;
            }
            .modern-table { min-width: 600px; }
            canvas { max-width: 100% !important; height: auto !important; }
            .form-row { display: block !important; }
            .form-row > div { width: 100% !important; margin-bottom: 1rem; }
        }
    </style>
</head>
<body>

<!-- Overlay gelap saat sidebar terbuka di mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ===== SIDEBAR ===== -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <img src="<?= $base_url ?>assets/images/logo-sekolah.png" alt="Logo">
            </div>
            <div class="sidebar-logo-text">
                <span class="sidebar-logo-title">MTs. Al-Ihsan</span>
                <span class="sidebar-logo-sub">Batujajar</span>
            </div>
        </div>
        <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Tutup Sidebar">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-avatar">
            <i class="<?= $role == 'admin' ? 'fas fa-chalkboard-user' : 'fas fa-user-graduate' ?>"></i>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?= htmlspecialchars($nama) ?></div>
            <div class="sidebar-user-role"><?= $role == 'admin' ? 'Guru' : 'Siswa' ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <?php if ($role == 'admin'): ?>

                <li class="sidebar-menu-item">
                    <a href="<?= $base_url ?>admin/dashboard" class="sidebar-menu-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li class="sidebar-menu-item">
                    <a href="<?= $base_url ?>admin/bab" class="sidebar-menu-link">
                        <i class="fas fa-book"></i>
                        <span>Kelola Bab</span>
                    </a>
                </li>

                <li class="sidebar-menu-item sidebar-dropdown">
                    <button class="sidebar-menu-link sidebar-dropdown-btn" type="button">
                        <i class="fas fa-tools"></i>
                        <span>Perangkat Pembelajaran</span>
                        <i class="fas fa-chevron-down sidebar-chevron"></i>
                    </button>
                    <ul class="sidebar-submenu">
                        <li><a href="<?= $base_url ?>admin/atp"><i class="fas fa-list-check"></i> ATP</a></li>
                        <li><a href="<?= $base_url ?>admin/cp"><i class="fas fa-bullseye"></i> CP</a></li>
                        <li><a href="<?= $base_url ?>admin/modul_ajar.php"><i class="fas fa-chalkboard"></i> Modul Ajar</a></li>
                        <li><a href="<?= $base_url ?>admin/lkpd"><i class="fas fa-pen-alt"></i> LKPD</a></li>
                        <li><a href="<?= $base_url ?>admin/kktp"><i class="fas fa-bullseye"></i> KKTP</a></li>
                        <li><a href="<?= $base_url ?>admin/prota"><i class="fas fa-calendar-alt"></i> PROTA</a></li>
                        <li><a href="<?= $base_url ?>admin/promes"><i class="fas fa-calendar-week"></i> PROMES</a></li>
                        <li><a href="<?= $base_url ?>admin/jurnal"><i class="fas fa-journal-whills"></i> Jurnal</a></li>
                    </ul>
                </li>

                <li class="sidebar-menu-item sidebar-dropdown">
                    <button class="sidebar-menu-link sidebar-dropdown-btn" type="button">
                        <i class="fas fa-folder-open"></i>
                        <span>Administrasi</span>
                        <i class="fas fa-chevron-down sidebar-chevron"></i>
                    </button>
                    <ul class="sidebar-submenu">
                        <li><a href="<?= $base_url ?>admin/absensi"><i class="fas fa-clipboard-list"></i> Absensi</a></li>
                        <li><a href="<?= $base_url ?>admin/rekap_absensi"><i class="fas fa-chart-pie"></i> Rekap Absensi</a></li>
                        <li><a href="<?= $base_url ?>admin/kelas_siswa"><i class="fas fa-users"></i> Kelas &amp; Siswa</a></li>
                        <li><a href="<?= $base_url ?>admin/whiteboard"><i class="fas fa-chalkboard"></i> Papan Tulis Digital</a></li>
                        <li><a href="<?= $base_url ?>admin/tugas"><i class="fas fa-tasks"></i> Tugas &amp; Nilai</a></li>
                        <li><a href="<?= $base_url ?>admin/sikap"><i class="fas fa-heart"></i> Nilai Sikap</a></li>
                        <li><a href="<?= $base_url ?>admin/rekap_sikap"><i class="fas fa-chart-pie"></i> Rekap Nilai Sikap</a></li>
                        <li><a href="<?= $base_url ?>admin/olah_nilai"><i class="fas fa-chart-line"></i> Olah Nilai</a></li>
                    </ul>
                </li>

                <li class="sidebar-menu-item sidebar-dropdown">
                    <button class="sidebar-menu-link sidebar-dropdown-btn" type="button">
                        <i class="fas fa-quran"></i>
                        <span>Al-Qur'an &amp; Hadits</span>
                        <i class="fas fa-chevron-down sidebar-chevron"></i>
                    </button>
                    <ul class="sidebar-submenu">
                        <li><a href="<?= $base_url ?>mushaf"><i class="fas fa-quran"></i> Mushaf (Al-Qur'an)</a></li>
                        <li><a href="<?= $base_url ?>admin/hadits"><i class="fas fa-scroll"></i> Hadits</a></li>
                    </ul>
                </li>

                <li class="sidebar-menu-item">
                    <a href="<?= $base_url ?>admin/chat" class="sidebar-menu-link">
                        <i class="fas fa-comments"></i>
                        <span>Chat Kelas</span>
                    </a>
                </li>

                <?php if ($nama == 'Ilham Rizqiawan, S.Pd.'): ?>
                <li class="sidebar-menu-item">
                    <a href="<?= $base_url ?>admin/user_management" class="sidebar-menu-link">
                        <i class="fas fa-users-gear"></i>
                        <span>User Management</span>
                    </a>
                </li>
                <?php endif; ?>

                <li class="sidebar-menu-item">
                    <a href="<?= $base_url ?>admin/pengaturan" class="sidebar-menu-link">
                        <i class="fas fa-cog"></i>
                        <span>Pengaturan</span>
                    </a>
                </li>

            <?php elseif ($role == 'siswa'): ?>

                <li class="sidebar-menu-item">
                    <a href="<?= $base_url ?>siswa/dashboard" class="sidebar-menu-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="<?= $base_url ?>siswa/materi" class="sidebar-menu-link">
                        <i class="fas fa-book"></i>
                        <span>Materi</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="<?= $base_url ?>siswa/absensi_saya" class="sidebar-menu-link">
                        <i class="fas fa-folder-open"></i>
                        <span>Absensi Saya</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="<?= $base_url ?>siswa/tugas_saya" class="sidebar-menu-link">
                        <i class="fas fa-tasks"></i>
                        <span>Tugas Saya</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="<?= $base_url ?>siswa/nilai_saya" class="sidebar-menu-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Nilai Saya</span>
                    </a>
                </li>

                <li class="sidebar-menu-item sidebar-dropdown">
                    <button class="sidebar-menu-link sidebar-dropdown-btn" type="button">
                        <i class="fas fa-quran"></i>
                        <span>Al-Qur'an &amp; Hadits</span>
                        <i class="fas fa-chevron-down sidebar-chevron"></i>
                    </button>
                    <ul class="sidebar-submenu">
                        <li><a href="<?= $base_url ?>mushaf"><i class="fas fa-quran"></i> Mushaf (Al-Qur'an)</a></li>
                        <li><a href="<?= $base_url ?>siswa/hadits"><i class="fas fa-scroll"></i> Hadits</a></li>
                    </ul>
                </li>

                <li class="sidebar-menu-item">
                    <a href="<?= $base_url ?>siswa/chat" class="sidebar-menu-link">
                        <i class="fas fa-comments"></i>
                        <span>Chat Kelas</span>
                    </a>
                </li>
                <li class="sidebar-menu-item">
                    <a href="<?= $base_url ?>siswa/profil" class="sidebar-menu-link">
                        <i class="fas fa-user-circle"></i>
                        <span>Profil</span>
                    </a>
                </li>

            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="<?= $base_url ?>logout.php" class="sidebar-logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>

</aside>
<!-- ===== END SIDEBAR ===== -->

<!-- ===== TOPBAR ===== -->
<div class="topbar" id="topbar">
    <button class="topbar-toggle-btn" id="sidebarToggleBtn" aria-label="Buka/Tutup Menu">
        <i class="fas fa-bars"></i>
    </button>
    <div class="topbar-brand">
        <div class="topbar-logo-icon">
            <img src="<?= $base_url ?>assets/images/logo-sekolah.png" alt="Logo">
        </div>
        <div class="topbar-title">
            <span class="topbar-title-main">Digitalisasi Pembelajaran</span>
            <span class="topbar-title-sub">MTs. Al-Ihsan Batujajar &mdash; Kurikulum Merdeka</span>
        </div>
    </div>
    <div class="topbar-user">
        <div class="topbar-user-avatar">
            <i class="<?= $role == 'admin' ? 'fas fa-chalkboard-user' : 'fas fa-user-graduate' ?>"></i>
        </div>
        <div class="topbar-user-info">
            <span class="topbar-user-name"><?= htmlspecialchars($nama) ?></span>
            <span class="topbar-user-role"><?= $role == 'admin' ? 'Guru' : 'Siswa' ?></span>
        </div>
        <a href="<?= $base_url ?>logout.php" class="topbar-logout" aria-label="Logout">
            <i class="fas fa-sign-out-alt"></i>
            <span class="topbar-logout-text">Logout</span>
        </a>
    </div>
</div>
<!-- ===== END TOPBAR ===== -->

<!-- Wrapper konten utama (bergeser di desktop saat sidebar terbuka) -->
<div class="main-wrapper" id="mainWrapper">
<main class="dashboard-container">

<script>
(function () {
    'use strict';

    var sidebar     = document.getElementById('sidebar');
    var overlay     = document.getElementById('sidebarOverlay');
    var toggleBtn   = document.getElementById('sidebarToggleBtn');
    var closeBtn    = document.getElementById('sidebarCloseBtn');
    var mainWrapper = document.getElementById('mainWrapper');

    if (!sidebar || !overlay || !toggleBtn || !closeBtn || !mainWrapper) return;

    function openSidebar() {
        sidebar.classList.add('sidebar-open');
        overlay.classList.add('overlay-visible');
        if (window.innerWidth >= 992) {
            mainWrapper.classList.add('main-shifted');
        }
        localStorage.setItem('sidebarState', 'open');
    }

    function closeSidebar() {
        sidebar.classList.remove('sidebar-open');
        overlay.classList.remove('overlay-visible');
        mainWrapper.classList.remove('main-shifted');
        localStorage.setItem('sidebarState', 'closed');
    }

    function toggleSidebar() {
        if (sidebar.classList.contains('sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    toggleBtn.addEventListener('click', toggleSidebar);
    closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    window.addEventListener('resize', function () {
        if (window.innerWidth < 992) {
            mainWrapper.classList.remove('main-shifted');
        } else if (sidebar.classList.contains('sidebar-open')) {
            mainWrapper.classList.add('main-shifted');
        }
    });

    // Restore state sidebar di desktop
    if (window.innerWidth >= 992 && localStorage.getItem('sidebarState') === 'open') {
        openSidebar();
    }

    // Dropdown submenu
    var dropdownBtns = document.querySelectorAll('.sidebar-dropdown-btn');
    dropdownBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var parentLi = this.closest('.sidebar-dropdown');
            var isActive = parentLi.classList.contains('sidebar-dropdown-active');

            // Tutup semua dropdown lain
            document.querySelectorAll('.sidebar-dropdown').forEach(function (el) {
                el.classList.remove('sidebar-dropdown-active');
            });

            if (!isActive) {
                parentLi.classList.add('sidebar-dropdown-active');
            }
        });
    });

    // Tandai link aktif berdasarkan URL
    var currentUrl = window.location.href;

    document.querySelectorAll('.sidebar-submenu a').forEach(function (link) {
        if (link.href === currentUrl) {
            link.classList.add('active');
            var parentDropdown = link.closest('.sidebar-dropdown');
            if (parentDropdown) {
                parentDropdown.classList.add('sidebar-dropdown-active');
            }
        }
    });

    document.querySelectorAll('a.sidebar-menu-link').forEach(function (link) {
        if (link.href === currentUrl) {
            link.classList.add('active');
        }
    });

})();
</script>