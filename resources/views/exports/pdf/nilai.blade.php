<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Nilai</title>
    <style>
        @page { margin: 22px 24px 28px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9pt; color:#0f172a; }
        h2, h4 { text-align: center; margin: 4px 0; }
        h2 { color:#1e3a8a; letter-spacing:.5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 7.5pt; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 5px; text-align: center; }
        th { background-color: #334155; color: white; }
        tbody tr:nth-child(even) td { background:#f8fafc; }
        .rata { font-weight: bold; background-color: #dbeafe; color:#1e3a8a; }
        .header-info { text-align: center; margin: 8px auto 10px; font-size: 8pt; color: #334155; background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; padding:6px; width:70%; }
        .school-header { width: 100%; display: table; margin-bottom: 6px; }
        .school-logo { display: table-cell; width: 70px; vertical-align: middle; text-align: center; }
        .school-logo img { max-width: 56px; max-height: 56px; }
        .school-identity { display: table-cell; vertical-align: middle; text-align: center; padding-right: 70px; }
        .school-name { font-size: 15pt; font-weight: bold; text-transform: uppercase; }
        .school-address { font-size: 9pt; margin-top: 2px; }
        .school-contact { font-size: 8pt; color: #555; margin-top: 2px; }
        .header-line { border-top: 3px solid #1d4ed8; height: 0; margin-bottom: 10px; }
        .signature { width: 240px; margin-left: auto; margin-top: 24px; font-size: 8pt; text-align: left; }
        .signature-space { height: 42px; }
    </style>
</head>
<body>
    @include('exports.pdf._school-header')
    <h2>REKAP NILAI</h2>
    <h4>Kelas {{ $kelas->tingkat }} {{ $kelas->nama_kelas }}</h4>
    <div class="header-info">
        Semester {{ $reportSchool['semester'] ?? $labelSemester }} Tahun Ajaran {{ $reportSchool['school_year'] ?? ($taAktif?->tahun ?? '-') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIS</th>
                <th>Nama</th>
                @foreach($mapelList as $mp)
                <th>{{ $mp->nama_mapel }}</th>
                @endforeach
                <th>Rata²</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rekap as $i => $r)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $r['nis'] }}</td>
                <td style="text-align:left;">{{ $r['nama'] }}</td>
                @foreach($mapelList as $mp)
                <td>{{ $r['nilai'][$mp->id] ?? '-' }}</td>
                @endforeach
                <td class="rata">{{ $r['rata'] ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top:20px; font-size:8pt; text-align:right;">
        Dicetak pada: {{ date('d/m/Y H:i') }}
    </div>
    <div class="signature">
        Kepala Sekolah,
        <div class="signature-space"></div>
        <strong>{{ $reportSchool['principal_name'] }}</strong><br>
        @if($reportSchool['principal_nip'])
            NIP. {{ $reportSchool['principal_nip'] }}
        @elseif($reportSchool['principal_nuptk'])
            NUPTK. {{ $reportSchool['principal_nuptk'] }}
        @endif
    </div>
</body>
</html>
