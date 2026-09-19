<!DOCTYPE html>
<html lang="id">
<head>
    @php
        $inertiaSchoolName = school_setting('school_name', 'Nama Sekolah');
        $inertiaAppName = 'LMS Sekolah';
        $inertiaLogoUrl = school_logo_url();
        $inertiaFaviconUrl = school_favicon_url();
        try {
            $inertiaTheme = \App\Models\Pengaturan::getValue('warna_tema', 'hijau');
        } catch (\Throwable) {
            $inertiaTheme = 'hijau';
        }
        $inertiaThemeColors = [
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
        $inertiaActiveTheme = $inertiaThemeColors[$inertiaTheme] ?? $inertiaThemeColors['hijau'];
    @endphp
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="application-name" content="{{ $inertiaAppName }}">
    <meta name="theme-color" content="{{ $inertiaActiveTheme['primary'] }}">
    <title inertia>{{ $inertiaAppName }} - {{ $inertiaSchoolName }}</title>
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
    <link rel="icon" href="{{ $inertiaFaviconUrl }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --app-primary: {{ $inertiaActiveTheme['primary'] }};
            --app-primary-dark: {{ $inertiaActiveTheme['sidebar'] }};
            --app-accent: {{ $inertiaActiveTheme['secondary'] }};
            --app-bg: #f8fafc;
            --app-radius: 0.875rem;
            --app-shadow: 0 4px 12px rgba(0,0,0,.08);
            --font-sans: 'Plus Jakarta Sans', 'Segoe UI', system-ui, sans-serif;
            --primary-500: {{ $inertiaActiveTheme['primary'] }};
            --primary-600: color-mix(in srgb, {{ $inertiaActiveTheme['primary'] }} 88%, black);
            --primary-700: color-mix(in srgb, {{ $inertiaActiveTheme['sidebar'] }} 78%, black);
            --primary-800: color-mix(in srgb, {{ $inertiaActiveTheme['sidebar'] }} 66%, black);
            --primary-100: color-mix(in srgb, {{ $inertiaActiveTheme['primary'] }} 18%, white);
            --primary-50: color-mix(in srgb, {{ $inertiaActiveTheme['primary'] }} 8%, white);
            --primary-300: color-mix(in srgb, {{ $inertiaActiveTheme['primary'] }} 70%, white);
            --sidebar-bg: {{ $inertiaActiveTheme['sidebar'] }};
            --navbar-bg: {{ $inertiaActiveTheme['navbar'] }};
            --toast-success-bg: linear-gradient(135deg, {{ $inertiaActiveTheme['primary'] }}, {{ $inertiaActiveTheme['sidebar'] }});
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
            --shadow-green: 0 4px 16px color-mix(in srgb, {{ $inertiaActiveTheme['primary'] }} 24%, transparent);
            --focus-ring: 0 0 0 3px color-mix(in srgb, {{ $inertiaActiveTheme['primary'] }} 18%, transparent);
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
    </style>
    @vite(['resources/css/app.css', 'resources/js/inertia.ts'])
    <link rel="stylesheet" href="{{ asset('css/login-isolation.css') }}">
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
