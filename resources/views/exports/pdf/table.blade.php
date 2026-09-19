<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 22px 24px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #0f172a; }
        .letterhead { display: table; width: 100%; border-bottom: 3px solid #1d4ed8; padding-bottom: 8px; margin-bottom: 12px; }
        .logo { display: table-cell; width: 72px; vertical-align: middle; text-align: center; }
        .logo img { max-width: 56px; max-height: 56px; }
        .identity { display: table-cell; vertical-align: middle; text-align: center; padding-right: 72px; }
        .school { font-size: 15px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
        .meta { margin-top: 2px; font-size: 8px; color: #475569; }
        .title-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 8px 10px; margin-bottom: 10px; text-align: center; }
        h1 { font-size: 13px; margin: 0 0 4px; color: #1e3a8a; }
        .context { font-size: 9px; color: #334155; }
        .chips { text-align: center; margin-top: 6px; }
        .chip { display: inline-block; border: 1px solid #cbd5e1; background: #fff; border-radius: 10px; padding: 3px 7px; margin: 0 2px; font-size: 8px; color: #334155; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 5px 6px; vertical-align: top; }
        th { background: #334155; color: white; font-weight: 700; text-align: center; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        td:first-child { text-align: center; width: 24px; }
        .empty { text-align: center; color: #64748b; padding: 20px; }
        .printed { margin-top: 12px; text-align: right; color: #64748b; font-size: 8px; }
        .signature { width: 230px; margin-left: auto; margin-top: 20px; font-size: 8px; text-align: left; }
        .signature-space { height: 38px; }
    </style>
</head>
<body>
    <div class="letterhead">
        <div class="logo">
            <img src="{{ $reportSchool['logo'] }}" alt="Logo">
        </div>
        <div class="identity">
            <div class="school">{{ $reportSchool['name'] }}</div>
            <div class="meta">{{ $reportSchool['address'] }}</div>
            <div class="meta">
                @if($reportSchool['phone']) Telp. {{ $reportSchool['phone'] }} @endif
                @if($reportSchool['email']) {{ $reportSchool['phone'] ? ' | ' : '' }}Email: {{ $reportSchool['email'] }} @endif
                @if($reportSchool['website']) {{ ($reportSchool['phone'] || $reportSchool['email']) ? ' | ' : '' }}{{ $reportSchool['website'] }} @endif
            </div>
        </div>
    </div>

    <div class="title-box">
        <h1>{{ $title }}</h1>
        <div class="context">{{ $context }}</div>
        <div class="chips">
            <span class="chip">TA {{ $reportSchool['school_year'] }}</span>
            <span class="chip">Semester {{ $reportSchool['semester'] }}</span>
            <span class="chip">Dicetak {{ date('d/m/Y H:i') }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="{{ count($headers) }}">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="printed">Dicetak pada: {{ date('d/m/Y H:i') }}</div>
    <div class="signature">
        {{ $signer['role'] ?? 'Kepala Sekolah' }},
        <div class="signature-space"></div>
        <strong>{{ $signer['name'] ?? ($reportSchool['principal_name'] ?? '-') }}</strong><br>
        @if(!empty($signer['id_label']) && !empty($signer['id_value']))
            {{ $signer['id_label'] }}. {{ $signer['id_value'] }}
        @endif
    </div>
</body>
</html>
