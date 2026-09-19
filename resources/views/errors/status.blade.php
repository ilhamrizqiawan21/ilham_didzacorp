@php
    $code = $code ?? 500;
    $title = $title ?? 'Terjadi kesalahan sistem';
    $message = $message ?? 'Silakan coba lagi beberapa saat lagi.';
    $detail = $detail ?? null;
    $primary = $primary ?? '#198754';
    $primaryDark = $primaryDark ?? '#166534';
    $icon = $icon ?? 'alert';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }}</title>
    <style>
        :root {
            color-scheme: light;
            --primary: {{ $primary }};
            --primary-dark: {{ $primaryDark }};
            --bg: #f8fafc;
            --surface: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #e5e7eb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .notice {
            width: min(100%, 520px);
            padding: 34px 28px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: var(--surface);
            box-shadow: 0 16px 42px rgba(15, 23, 42, 0.08);
            text-align: center;
        }

        .notice-code {
            margin-bottom: 14px;
            color: var(--primary);
            font-size: 0.92rem;
            font-weight: 750;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .notice-icon {
            width: 76px;
            height: 76px;
            display: inline-grid;
            place-items: center;
            margin-bottom: 20px;
            border-radius: 50%;
            background: color-mix(in srgb, var(--primary) 14%, white);
            color: var(--primary-dark);
        }

        h1 {
            margin: 0 0 10px;
            font-size: 1.45rem;
            line-height: 1.35;
            font-weight: 750;
        }

        p {
            margin: 0;
            color: var(--muted);
            font-size: 0.98rem;
            line-height: 1.7;
        }

        .notice-detail {
            margin-top: 12px;
            padding: 10px 12px;
            border-radius: 9px;
            background: #f8fafc;
            color: #475569;
            font-size: 0.86rem;
            line-height: 1.5;
            text-align: left;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        a,
        button {
            appearance: none;
            border: 0;
            border-radius: 10px;
            padding: 10px 16px;
            font: inherit;
            font-weight: 650;
            text-decoration: none;
            cursor: pointer;
        }

        a {
            background: var(--primary);
            color: #ffffff;
        }

        button {
            background: #eef2f7;
            color: var(--text);
        }
    </style>
</head>
<body>
    <main class="notice" role="main" aria-labelledby="notice-title">
        <div class="notice-code">{{ $code }}</div>
        <div class="notice-icon" aria-hidden="true">
            @if($icon === 'search')
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none" role="img" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9.8 5.2a7 7 0 1 1-2.5 2.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M4 4l5.8 5.8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M11 11h4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M11 14h3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
            @elseif($icon === 'clock')
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none" role="img" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 7v5l3 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8"/>
                </svg>
            @elseif($icon === 'lock')
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none" role="img" xmlns="http://www.w3.org/2000/svg">
                    <path d="M7 11V8a5 5 0 0 1 10 0v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    <path d="M6.5 11h11A1.5 1.5 0 0 1 19 12.5v6A1.5 1.5 0 0 1 17.5 20h-11A1.5 1.5 0 0 1 5 18.5v-6A1.5 1.5 0 0 1 6.5 11Z" stroke="currentColor" stroke-width="1.8"/>
                </svg>
            @elseif($icon === 'tool')
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none" role="img" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14.7 6.3a4.5 4.5 0 0 0-5.1 5.8L4.9 16.8a2 2 0 0 0 2.8 2.8l4.7-4.7a4.5 4.5 0 0 0 5.8-5.1l-2.8 2.8-2-2 2.8-2.8Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7.4 17.4h.01" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/>
                </svg>
            @else
                <svg width="38" height="38" viewBox="0 0 24 24" fill="none" role="img" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 8v5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                    <path d="M12 16.5h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                    <path d="M10.4 4.8 3.2 17.4A2 2 0 0 0 4.9 20h14.2a2 2 0 0 0 1.7-2.6L13.6 4.8a1.85 1.85 0 0 0-3.2 0Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                </svg>
            @endif
        </div>
        <h1 id="notice-title">{{ $title }}</h1>
        <p>{{ $message }}</p>
        @if($detail)
            <p class="notice-detail"><strong>Detail:</strong> {{ $detail }}</p>
        @endif
        <div class="actions">
            <a href="{{ url('/') }}">Ke Halaman Utama</a>
            <button type="button" onclick="history.back()">Kembali</button>
        </div>
    </main>
</body>
</html>
