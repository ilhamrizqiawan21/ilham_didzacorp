<!DOCTYPE html>
<html lang="id">
<head>
    @php
        $layoutSchoolName = school_setting('school_name', 'Nama Sekolah');
        $layoutSchoolShortName = school_setting('school_short_name', 'LMS');
        $layoutAppName = 'LMS Sekolah';
        $layoutLogoUrl = school_logo_url();
        $layoutFaviconUrl = school_favicon_url();
        $layoutTheme = \App\Models\Pengaturan::getValue('warna_tema', 'hijau');
        $layoutThemeColors = [
            'hijau' => [
                'primary' => '#198754',
                'secondary' => '#0d6efd',
                'sidebar' => '#166534',
                'navbar' => '#198754',
            ],
            'biru-azure' => [
                'primary' => '#0d6efd',
                'secondary' => '#22c55e',
                'sidebar' => '#1d4ed8',
                'navbar' => '#0d6efd',
            ],
            'biru-aqua' => [
                'primary' => '#0891b2',
                'secondary' => '#14b8a6',
                'sidebar' => '#0e7490',
                'navbar' => '#0891b2',
            ],
            'indigo' => [
                'primary' => '#4f46e5',
                'secondary' => '#06b6d4',
                'sidebar' => '#3730a3',
                'navbar' => '#4338ca',
            ],
            'marun' => [
                'primary' => '#be123c',
                'secondary' => '#f59e0b',
                'sidebar' => '#881337',
                'navbar' => '#be123c',
            ],
        ];
        $layoutActiveTheme = $layoutThemeColors[$layoutTheme] ?? $layoutThemeColors['hijau'];
        $layoutPrimaryColor = $layoutActiveTheme['primary'];
        $layoutSecondaryColor = $layoutActiveTheme['secondary'];
        $layoutSidebarColor = $layoutActiveTheme['sidebar'];
        $layoutNavbarColor = $layoutActiveTheme['navbar'];
        $layoutPageTitle = trim($__env->yieldContent('page_title')) ?: trim($__env->yieldContent('title')) ?: $layoutAppName;
        $layoutRole = auth()->check() ? auth()->user()->role?->nama_role : null;
        $layoutRoleLabel = match($layoutRole) {
            'admin' => 'Admin',
            'guru' => 'Guru',
            'siswa' => 'Siswa',
            'kepala_sekolah' => 'Kepala Sekolah',
            default => $layoutRole,
        };
        $layoutProfileRoute = match($layoutRole) {
            'guru' => route('guru.profil'),
            'siswa' => route('siswa.profil'),
            default => null,
        };
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="application-name" content="{{ $layoutAppName }}">
    <meta name="theme-color" content="{{ $layoutPrimaryColor }}">
    <title>@yield('title', $layoutAppName . ' - ' . $layoutSchoolName)</title>
    <script>
        (() => {
            const key = 'lms.color-mode';
            const stored = (() => {
                try { return window.localStorage.getItem(key); } catch { return null; }
            })();
            const mode = ['light', 'dark'].includes(stored)
                ? stored
                : (window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-bs-theme', mode);
            document.documentElement.style.colorScheme = mode;
        })();
    </script>
    <link rel="icon" href="{{ $layoutFaviconUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @else
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    @endif
    <style>
        :root {
            --app-primary: {{ $layoutPrimaryColor }};
            --app-primary-dark: {{ $layoutSidebarColor }};
            --app-accent: {{ $layoutSecondaryColor }};
            --app-bg: #f8fafc;
            --app-radius: 0.875rem;
            --app-shadow: 0 4px 12px rgba(0,0,0,.08);
            --font-sans: 'Plus Jakarta Sans', 'Segoe UI', system-ui, sans-serif;
            --primary-500: {{ $layoutPrimaryColor }};
            --primary-600: color-mix(in srgb, {{ $layoutPrimaryColor }} 88%, black);
            --primary-700: color-mix(in srgb, {{ $layoutSidebarColor }} 78%, black);
            --primary-800: color-mix(in srgb, {{ $layoutSidebarColor }} 66%, black);
            --primary-100: color-mix(in srgb, {{ $layoutPrimaryColor }} 18%, white);
            --primary-50: color-mix(in srgb, {{ $layoutPrimaryColor }} 8%, white);
            --primary-300: color-mix(in srgb, {{ $layoutPrimaryColor }} 70%, white);
            --sidebar-bg: {{ $layoutSidebarColor }};
            --navbar-bg: {{ $layoutNavbarColor }};
            --toast-success-bg: linear-gradient(135deg, {{ $layoutPrimaryColor }}, {{ $layoutSidebarColor }});
            --gold-400: #fbbf24;
            --gold-500: #f59e0b;
            --gold-600: #d97706;
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --surface-body: var(--app-bg);
            --surface-card: #ffffff;
            --surface-muted: var(--gray-50);
            --text-strong: var(--gray-800);
            --text-body: var(--gray-700);
            --text-muted: var(--gray-500);
            --status-success-bg: #dcfce7;
            --status-success-text: #166534;
            --status-warning-bg: #fef3c7;
            --status-warning-text: #92400e;
            --status-danger-bg: #fee2e2;
            --status-danger-text: #991b1b;
            --status-info-bg: #dbeafe;
            --status-info-text: #1e40af;
            --sidebar-width: 272px;
            --topbar-height: 58px;
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.25rem;
            --space-6: 1.5rem;
            --space-8: 2rem;
            --radius-sm: 0.375rem;
            --radius-md: 0.625rem;
            --radius-lg: var(--app-radius);
            --radius-xl: 1.125rem;
            --radius-full: 9999px;
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-default: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-bounce: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: var(--app-shadow), 0 2px 4px rgba(0,0,0,0.04);
            --shadow-lg: 0 10px 24px rgba(0,0,0,0.10), 0 4px 8px rgba(0,0,0,0.06);
            --shadow-green: 0 4px 16px color-mix(in srgb, {{ $layoutPrimaryColor }} 24%, transparent);
            --focus-ring: 0 0 0 3px color-mix(in srgb, {{ $layoutPrimaryColor }} 18%, transparent);
            --card-radius: var(--app-radius);
            --card-padding: var(--space-5);
            --card-shadow: var(--app-shadow);
            --btn-font-size: 0.85rem;
            --btn-font-size-sm: 0.78rem;
            --btn-padding-y: 0.45rem;
            --btn-padding-x: 1rem;
            --btn-padding-y-sm: 0.35rem;
            --btn-padding-x-sm: 0.75rem;
            --btn-icon-size: 32px;
            --input-font-size: 0.88rem;
            --input-padding-y: 0.5rem;
            --input-padding-x: 0.75rem;
            --table-font-size: 0.85rem;
            --table-heading-font-size: 0.82rem;
            --table-cell-padding-y: 0.65rem;
            --table-cell-padding-x: 0.75rem;
            --badge-font-size: 0.72rem;
            --badge-padding-y: 5px;
            --badge-padding-x: 10px;
            --bs-primary: var(--app-primary);
            --bs-success: var(--app-primary);
            --bs-warning: var(--gold-500);
            --bs-link-color: var(--primary-600);
            --bs-link-hover-color: var(--primary-700);
        }
        .text-primary,
        .text-success {
            color: var(--primary-600) !important;
        }
        .bg-primary,
        .bg-success {
            background-color: var(--app-primary) !important;
        }
        .border-primary,
        .border-success {
            border-color: var(--app-primary) !important;
        }
        .btn-primary,
        .btn-success {
            background: linear-gradient(135deg, var(--primary-500), var(--primary-600)) !important;
            border-color: var(--primary-600) !important;
        }
        .btn-primary:hover,
        .btn-success:hover {
            background: linear-gradient(135deg, var(--primary-600), var(--primary-700)) !important;
            border-color: var(--primary-700) !important;
        }
        .btn-outline-primary,
        .btn-outline-success {
            color: var(--primary-600) !important;
            border-color: var(--app-primary) !important;
        }
        .btn-outline-primary:hover,
        .btn-outline-success:hover {
            background: var(--app-primary) !important;
            color: #fff !important;
        }
        .form-check-input:checked {
            background-color: var(--app-primary) !important;
            border-color: var(--app-primary) !important;
        }
        .page-link {
            color: var(--primary-600);
        }
        .active > .page-link,
        .page-link.active {
            background-color: var(--app-primary);
            border-color: var(--app-primary);
        }
        .topbar {
            background:linear-gradient(135deg, var(--navbar-bg), var(--primary-700));
        }
        .sidebar {
            background:linear-gradient(165deg, var(--sidebar-bg) 0%, var(--primary-700) 45%, var(--primary-800) 100%);
        }
    </style>
    @stack('styles')
</head>
<body>
    <a href="#mainContent" class="skip-link">Lewati ke konten utama</a>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Topbar -->
    <div class="topbar">
        <button class="topbar-toggle-btn" type="button" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false" data-sidebar-toggle>
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <div class="topbar-brand">
            <div class="topbar-logo-icon">
                <img src="{{ $layoutLogoUrl }}" alt="Logo {{ $layoutSchoolName }}" class="app-logo-sm" width="32" height="32" decoding="async">
            </div>
            <div class="topbar-title">
                <span class="topbar-title-main">{{ $layoutAppName }}</span>
                <span class="topbar-title-sub">{{ $layoutSchoolName }}</span>
            </div>
        </div>
        <div class="topbar-context">
            <span class="topbar-context-label">{{ $layoutRoleLabel }}</span>
            <span class="topbar-context-title">{{ $layoutPageTitle }}</span>
        </div>
        <div class="topbar-actions">
            @php
                $topbarRole = auth()->user()->role?->nama_role;
                $topbarNotifRoute = match($topbarRole) {
                    'guru' => route('guru.notifikasi.index'),
                    'siswa' => route('siswa.notifikasi.index'),
                    default => null,
                };
                $topbarShouldLoadNotifs = $topbarNotifRoute && in_array($topbarRole, ['guru', 'siswa'], true);
                $topbarUnread = 0;
                $topbarNotifs = collect();

                if ($topbarShouldLoadNotifs) {
                    $topbarUnread = \App\Models\Notifikasi::where('user_id', auth()->id())->where('is_read', false)->count();
                    $topbarNotifs = \App\Models\Notifikasi::where('user_id', auth()->id())
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get();
                }
            @endphp
            @if($topbarShouldLoadNotifs)
            <div class="dropdown">
                <button class="btn btn-sm position-relative topbar-icon-btn" type="button" data-bs-toggle="dropdown" title="Notifikasi" aria-label="Notifikasi">
                    <i class="bi bi-bell-fill" aria-hidden="true"></i>
                    @if($topbarUnread > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-count">
                        {{ $topbarUnread > 99 ? '99+' : $topbarUnread }}
                    </span>
                    @endif
                </button>
                <ul class="dropdown-menu dropdown-menu-end notification-menu">
                    <li class="dropdown-item-text d-flex justify-content-between align-items-center">
                        <strong class="notification-title">Notifikasi</strong>
                        @if($topbarUnread > 0)
                        <form action="{{ $topbarRole === 'guru' ? route('guru.notifikasi.mark-all-read') : route('siswa.notifikasi.mark-all-read') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-link btn-sm text-decoration-none notification-mark-all">Tandai semua dibaca</button>
                        </form>
                        @endif
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    @if($topbarNotifs->isNotEmpty())
                        @foreach($topbarNotifs as $tn)
                        <li>
                            <form action="{{ $topbarRole === 'guru' ? route('guru.notifikasi.mark-read', $tn) : route('siswa.notifikasi.mark-read', $tn) }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item notification-link {{ $tn->is_read ? '' : 'unread' }}">
                                    <div class="notification-item-title">{{ $tn->judul }}</div>
                                    <div class="notification-item-message">{{ Str::limit($tn->pesan, 80) }}</div>
                                    <small class="notification-item-time">{{ $tn->created_at ? \Carbon\Carbon::parse($tn->created_at)->diffForHumans() : '' }}</small>
                                </button>
                            </form>
                        </li>
                        @endforeach
                    @else
                        <li><span class="dropdown-item-text text-muted text-center notification-action-link">Belum ada notifikasi</span></li>
                    @endif
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a href="{{ $topbarNotifRoute }}" class="dropdown-item text-center notification-action-link">Lihat Semua Notifikasi</a></li>
                </ul>
            </div>
            @endif
            <button class="btn btn-sm topbar-icon-btn theme-toggle-btn" type="button" data-theme-toggle aria-label="Aktifkan mode gelap" title="Aktifkan mode gelap" aria-pressed="false">
                <i class="bi bi-moon-stars-fill" data-theme-toggle-icon aria-hidden="true"></i>
            </button>
            <span class="d-none d-lg-inline me-2 topbar-user-name">{{ auth()->user()->nama_lengkap }}</span>
            <div class="dropdown">
                <button class="btn btn-sm dropdown-toggle topbar-account-btn" type="button" data-bs-toggle="dropdown" aria-label="Menu akun">
                    <i class="bi bi-person-circle me-1" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text fw-bold">{{ auth()->user()->nama_lengkap }}</span></li>
                    <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->username }} - {{ $layoutRoleLabel }}</span></li>
                    <li><hr class="dropdown-divider"></li>
                    @if($layoutProfileRoute)
                    <li><a href="{{ $layoutProfileRoute }}" class="dropdown-item"><i class="bi bi-person-gear me-1" aria-hidden="true"></i> Profil</a></li>
                    @endif
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-1" aria-hidden="true"></i> Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo-icon">
                <img src="{{ $layoutLogoUrl }}" alt="Logo {{ $layoutSchoolName }}" class="app-logo-md" width="36" height="36" decoding="async">
            </div>
            <div class="sidebar-logo-text">
                <span class="sidebar-logo-title">{{ $layoutAppName }}</span>
                <span class="sidebar-logo-sub">{{ $layoutSchoolName }}</span>
            </div>
        </div>
        <div class="sidebar-user">
            <div class="sidebar-user-avatar"><i class="bi bi-person-fill"></i></div>
            <div>
                <div class="sidebar-user-name">{{ auth()->user()->nama_lengkap }}</div>
                <div class="sidebar-user-role">{{ $layoutRoleLabel }}</div>
            </div>
        </div>
        <nav class="sidebar-nav" aria-label="Navigasi utama">
            <ul class="sidebar-menu">
                @php
                    $role = auth()->user()->role?->nama_role;
                    $prefix = match($role) {
                        'admin' => 'admin',
                        'guru' => 'guru',
                        'siswa' => 'siswa',
                        'kepala_sekolah' => 'kepsek',
                        default => '#'
                    };
                @endphp
                @include('layouts.sidebar')
            </ul>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-footer-title">{{ $layoutSchoolShortName }}</div>
            <div class="sidebar-footer-sub">Tahun {{ date('Y') }}</div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content" id="mainContent" tabindex="-1">
        <div class="page-content">
            @if(session('success'))
                <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
                    <i class="bi bi-x-circle-fill"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            @endif
            @yield('content')
        </div>
        <footer>
            &copy; {{ date('Y') }} {{ $layoutSchoolName }} — {{ $layoutAppName }}
        </footer>
    </main>

    @unless(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    @endunless
    @stack('scripts')
</body>
</html>
