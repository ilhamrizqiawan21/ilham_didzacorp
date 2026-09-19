@extends('layouts.app')

@section('title', 'Rekap Sikap')

@section('content')
<div class="page-header">
    <h4><i class="bi bi-file-earmark-text-fill me-2"></i> Rekap Sikap Spiritual & Sosial</h4>
</div>

<div class="card mb-3">
    <div class="card-header"><i class="bi bi-funnel me-1"></i> Filter</div>
    <div class="card-body">
        <form class="row g-2" method="GET" onchange="this.submit()">
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
                <a href="{{ route('guru.rekap-sikap') }}" class="btn btn-sm btn-outline-secondary w-100" title="Reset filter" aria-label="Reset filter rekap sikap">↻</a>
            </div>
        </form>
    </div>
</div>

{{-- Sikap Spiritual --}}
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-star-fill me-2"></i> Sikap Spiritual (KI-1)</span>
        <span class="badge bg-secondary">{{ $sikapSpiritual->count() }} siswa</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0" style="font-size:0.8rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width:35px;">#</th>
                        <th>Nama Siswa</th>
                        <th class="d-none d-md-table-cell">Kelas</th>
                        <th class="text-center">Taqwa</th>
                        <th class="text-center">Kejujuran</th>
                        <th class="text-center">Disiplin</th>
                        <th class="text-center">Sabar</th>
                        <th class="text-center">Syukur</th>
                        <th class="text-center">Tawadhu</th>
                        <th class="text-center" style="background:var(--gray-100);">Rata²</th>
                    </tr>
                </thead>
                <tbody>
                    @php $spFields = ['taqwa','kejujuran','disiplin','sabar','syukur','tawadhu']; @endphp
                    @if(!blank($sikapSpiritual))
                        @foreach($sikapSpiritual as $i => $s)
                    <tr>
                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                        <td>{{ $s['siswa']->user->nama_lengkap ?? $s['siswa']->nis ?? '—' }}</td>
                        <td class="d-none d-md-table-cell">{{ $s['siswa']->kelas->nama_kelas ?? '—' }}</td>
                        @foreach($spFields as $f)
                        @php $v = $s[$f] ?? 0; @endphp
                        <td class="text-center">
                            <span class="badge bg-{{ $v >= 4 ? 'success' : ($v >= 3 ? 'warning text-dark' : 'danger') }}">{{ $v }}</span>
                        </td>
                        @endforeach
                        <td class="text-center">
                            <strong style="color:{{ ($s['rata'] ?? 0) >= 4 ? '#166534' : (($s['rata'] ?? 0) >= 3 ? '#92400e' : '#991b1b') }};">
                                {{ $s['rata'] ?? '—' }}
                            </strong>
                        </td>
                    </tr>
                        @endforeach
                    @else
                    <tr><td colspan="10" class="text-center text-muted py-3">Belum ada data.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Sikap Sosial --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people-fill me-2"></i> Sikap Sosial (KI-2)</span>
        <span class="badge bg-secondary">{{ $sikapSosial->count() }} siswa</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0" style="font-size:0.8rem;">
                <thead class="table-light">
                    <tr>
                        <th style="width:35px;">#</th>
                        <th>Nama Siswa</th>
                        <th class="d-none d-md-table-cell">Kelas</th>
                        <th class="text-center">Empati</th>
                        <th class="text-center">Kerjasama</th>
                        <th class="text-center">Toleransi</th>
                        <th class="text-center">Percaya Diri</th>
                        <th class="text-center">Komunikasi</th>
                        <th class="text-center" style="background:var(--gray-100);">Rata²</th>
                    </tr>
                </thead>
                <tbody>
                    @php $soFields = ['empati','kerjasama','toleransi','percaya_diri','komunikasi']; @endphp
                    @if(!blank($sikapSosial))
                        @foreach($sikapSosial as $i => $s)
                    <tr>
                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                        <td>{{ $s['siswa']->user->nama_lengkap ?? $s['siswa']->nis ?? '—' }}</td>
                        <td class="d-none d-md-table-cell">{{ $s['siswa']->kelas->nama_kelas ?? '—' }}</td>
                        @foreach($soFields as $f)
                        @php $v = $s[$f] ?? 0; @endphp
                        <td class="text-center">
                            <span class="badge bg-{{ $v >= 4 ? 'success' : ($v >= 3 ? 'warning text-dark' : 'danger') }}">{{ $v }}</span>
                        </td>
                        @endforeach
                        <td class="text-center">
                            <strong style="color:{{ ($s['rata'] ?? 0) >= 4 ? '#166534' : (($s['rata'] ?? 0) >= 3 ? '#92400e' : '#991b1b') }};">
                                {{ $s['rata'] ?? '—' }}
                            </strong>
                        </td>
                    </tr>
                        @endforeach
                    @else
                    <tr><td colspan="9" class="text-center text-muted py-3">Belum ada data.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
