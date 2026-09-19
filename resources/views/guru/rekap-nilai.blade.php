@extends('layouts.app')

@section('title', 'Rekap Nilai')

@section('content')
<div class="page-header">
    <h4><i class="bi bi-file-earmark-bar-graph-fill me-2"></i> Rekap Nilai Siswa</h4>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-funnel me-1"></i> Filter</div>
    <div class="card-body">
        <form class="row g-2" method="GET">
            <div class="col-md-4">
                <select name="kelas_mapel_id" class="form-select form-select-sm">
                    <option value="">Semua Kelas & Mapel</option>
                    @foreach($kelasMapel as $km)
                    <option value="{{ $km->id }}" {{ request('kelas_mapel_id') == $km->id ? 'selected' : '' }}>
                        {{ $km->kelas->nama_kelas ?? '—' }} — {{ $km->mataPelajaran->nama_mapel ?? '—' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="semester" class="form-select form-select-sm">
                    <option value="1" {{ $semester == '1' ? 'selected' : '' }}>Semester 1</option>
                    <option value="2" {{ $semester == '2' ? 'selected' : '' }}>Semester 2</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100" type="submit"><i class="bi bi-search" aria-hidden="true"></i> Tampilkan</button>
            </div>
            <div class="col-md-1">
                <a href="{{ route('guru.rekap-nilai') }}" class="btn btn-sm btn-outline-secondary w-100" title="Reset filter" aria-label="Reset filter rekap nilai">↻</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0" style="font-size:0.82rem;">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" style="width:35px;">#</th>
                        <th rowspan="2">Nama Siswa</th>
                        <th rowspan="2" class="d-none d-md-table-cell">Kelas</th>
                        <th rowspan="2">Mapel</th>
                        <th colspan="4" class="text-center bg-success bg-opacity-10">Sumatif</th>
                        <th class="text-center bg-success bg-opacity-10">Harian</th>
                        <th class="text-center" style="background:#fef3c7;">STS</th>
                        <th class="text-center" style="background:#fef3c7;">SAS</th>
                        <th class="text-center" style="background:#fee2e2;">SAT</th>
                        <th rowspan="2" class="text-center" style="background:var(--gray-100);">Rata² Akhir</th>
                        <th rowspan="2" class="text-center">Predikat</th>
                    </tr>
                    <tr class="table-light">
                        <th class="text-center">1</th><th class="text-center">2</th><th class="text-center">3</th><th class="text-center">4</th>
                        <th class="text-center"></th><th class="text-center"></th><th class="text-center"></th><th class="text-center"></th>
                    </tr>
                </thead>
                <tbody>
                    @if(!blank($nilai))
                        @foreach($nilai as $i => $n)
                    @php
                        $rata = $n->rata_akhir;
                        $color = $rata >= 92 ? '#166534' : ($rata >= 83 ? '#1e40af' : ($rata >= 75 ? '#92400e' : '#991b1b'));
                        $predikat = $rata >= 92 ? 'A' : ($rata >= 83 ? 'B' : ($rata >= 75 ? 'C' : 'D'));
                        $predBg = $rata >= 92 ? 'success' : ($rata >= 83 ? 'primary' : ($rata >= 75 ? 'warning text-dark' : 'danger'));
                    @endphp
                    <tr>
                        <td class="text-center text-muted">{{ $nilai->firstItem() + $i }}</td>
                        <td>{{ $n->siswa->user->nama_lengkap ?? $n->siswa->nis ?? '—' }}</td>
                        <td class="d-none d-md-table-cell">{{ $n->siswa->kelas->nama_kelas ?? '—' }}</td>
                        <td>{{ $n->kelasMapel->mataPelajaran->nama_mapel ?? '—' }}</td>
                        <td class="text-center">{{ $n->sum1 ?? '—' }}</td>
                        <td class="text-center">{{ $n->sum2 ?? '—' }}</td>
                        <td class="text-center">{{ $n->sum3 ?? '—' }}</td>
                        <td class="text-center">{{ $n->sum4 ?? '—' }}</td>
                        <td class="text-center">{{ $n->nilai_harian ?? '—' }}</td>
                        <td class="text-center">{{ $n->sts ?? '—' }}</td>
                        <td class="text-center">{{ $n->sas ?? '—' }}</td>
                        <td class="text-center">{{ $n->sat ?? '—' }}</td>
                        <td class="text-center">
                            <strong style="color:{{ $color }};">{{ $rata ? number_format($rata, 1) : '—' }}</strong>
                        </td>
                        <td class="text-center">
                            @if($rata)
                            <span class="badge bg-{{ $predBg }}">{{ $predikat }}</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                        @endforeach
                    @else
                    <tr><td colspan="14" class="text-center text-muted py-4">Tidak ada data nilai.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    @if($nilai->hasPages())
    <div class="card-footer">
        {{ $nilai->links() }}
    </div>
    @endif
</div>
@endsection
