@php
    $role = auth()->user()->role?->nama_role;
@endphp

@if($role == 'admin')
<li class="nav-section">Master Data</li>
<li><a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
<li><a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-people-fill"></i><span>Guru dan Staf</span></a></li>
<li><a href="{{ route('admin.kelas.index') }}" class="nav-link {{ request()->routeIs('admin.kelas.*') ? 'active' : '' }}"><i class="bi bi-building"></i><span>Data Kelas</span></a></li>
<li><a href="{{ route('admin.kelas-siswa.index') }}" class="nav-link {{ request()->routeIs('admin.kelas-siswa.*') ? 'active' : '' }}"><i class="bi bi-mortarboard-fill"></i><span>Kelas dan Siswa</span></a></li>
<li><a href="{{ route('admin.mata-pelajaran.index') }}" class="nav-link {{ request()->routeIs('admin.mata-pelajaran.*') ? 'active' : '' }}"><i class="bi bi-book-fill"></i><span>Mata Pelajaran</span></a></li>
<li><a href="{{ route('admin.kelas-mapel.index') }}" class="nav-link {{ request()->routeIs('admin.kelas-mapel.*') ? 'active' : '' }}"><i class="bi bi-diagram-3-fill"></i><span>Penugasan Guru</span></a></li>
<li><a href="{{ route('admin.tahun-ajaran.index') }}" class="nav-link {{ request()->routeIs('admin.tahun-ajaran.*') ? 'active' : '' }}"><i class="bi bi-calendar-event-fill"></i><span>Tahun Ajaran</span></a></li>

<li class="nav-section">Aktivitas</li>
<li><a href="{{ route('admin.pengumuman.index') }}" class="nav-link {{ request()->routeIs('admin.pengumuman.*') ? 'active' : '' }}"><i class="bi bi-megaphone-fill"></i><span>Pengumuman</span></a></li>
<li><a href="{{ route('admin.kalender') }}" class="nav-link {{ request()->routeIs('admin.kalender') ? 'active' : '' }}"><i class="bi bi-calendar3"></i><span>Kalender</span></a></li>

<li class="nav-section">Laporan</li>
<li><a href="{{ route('admin.rekap.absensi') }}" class="nav-link {{ request()->routeIs('admin.rekap.absensi') ? 'active' : '' }}"><i class="bi bi-clipboard-check-fill"></i><span>Absensi</span></a></li>
<li><a href="{{ route('admin.rekap.nilai') }}" class="nav-link {{ request()->routeIs('admin.rekap.nilai') ? 'active' : '' }}"><i class="bi bi-bar-chart-fill"></i><span>Nilai</span></a></li>
<li><a href="{{ route('admin.rekap.sikap') }}" class="nav-link {{ request()->routeIs('admin.rekap.sikap') ? 'active' : '' }}"><i class="bi bi-heart-fill"></i><span>Sikap</span></a></li>
<li><a href="{{ route('admin.rekap.tugas') }}" class="nav-link {{ request()->routeIs('admin.rekap.tugas') ? 'active' : '' }}"><i class="bi bi-journal-check"></i><span>Tugas</span></a></li>

<li class="nav-section">Sistem</li>
<li><a href="{{ route('admin.pengaturan') }}" class="nav-link {{ request()->routeIs('admin.pengaturan') || request()->routeIs('admin.school-settings.*') ? 'active' : '' }}"><i class="bi bi-gear-fill"></i><span>Pengaturan</span></a></li>
<li><a href="{{ route('admin.log-login') }}" class="nav-link {{ request()->routeIs('admin.log-login') ? 'active' : '' }}"><i class="bi bi-clock-history"></i><span>Log Login</span></a></li>
<li><a href="{{ route('admin.log-error') }}" class="nav-link {{ request()->routeIs('admin.log-error') ? 'active' : '' }}"><i class="bi bi-bug-fill"></i><span>Log Error</span></a></li>
<li><a href="{{ route('admin.blocked-ips') }}" class="nav-link {{ request()->routeIs('admin.blocked-ips') ? 'active' : '' }}"><i class="bi bi-shield-fill-x"></i><span>IP Diblokir</span></a></li>

@elseif($role == 'guru')
@php
    $hasWaliKelasAktif = \App\Models\WaliKelas::where('guru_id', auth()->id())->aktif()->exists();
@endphp
<li class="nav-section">Mengajar</li>
<li><a href="{{ route('guru.dashboard') }}" class="nav-link {{ request()->routeIs('guru.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
<li><a href="{{ route('guru.absensi.index') }}" class="nav-link {{ request()->routeIs('guru.absensi.*') ? 'active' : '' }}"><i class="bi bi-clipboard-check-fill"></i><span>Absensi</span></a></li>
<li><a href="{{ route('guru.materi.index') }}" class="nav-link {{ request()->routeIs('guru.materi.*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text-fill"></i><span>Materi</span></a></li>
<li><a href="{{ route('guru.tugas.index') }}" class="nav-link {{ request()->routeIs('guru.tugas.*') ? 'active' : '' }}"><i class="bi bi-journal-fill"></i><span>Tugas</span></a></li>
<li><a href="{{ route('guru.nilai.index') }}" class="nav-link {{ request()->routeIs('guru.nilai.*') ? 'active' : '' }}"><i class="bi bi-bar-chart-fill"></i><span>Nilai</span></a></li>
<li><a href="{{ route('guru.sikap.index') }}" class="nav-link {{ request()->routeIs('guru.sikap.*') ? 'active' : '' }}"><i class="bi bi-emoji-smile-fill"></i><span>Sikap</span></a></li>

@if($hasWaliKelasAktif)
<li class="nav-section">Wali Kelas</li>
<li><a href="{{ route('guru.wali-kelas.index') }}" class="nav-link {{ request()->routeIs('guru.wali-kelas.*') ? 'active' : '' }}"><i class="bi bi-person-badge-fill"></i><span>Wali Kelas</span></a></li>
@endif

<li class="nav-section">Komunikasi</li>
<li><a href="{{ route('guru.kalender') }}" class="nav-link {{ request()->routeIs('guru.kalender') ? 'active' : '' }}"><i class="bi bi-calendar3"></i><span>Kalender</span></a></li>
<li><a href="{{ route('guru.pengumuman.index') }}" class="nav-link {{ request()->routeIs('guru.pengumuman.*') ? 'active' : '' }}"><i class="bi bi-megaphone-fill"></i><span>Pengumuman</span></a></li>
<li><a href="{{ route('guru.chat.index') }}" class="nav-link {{ request()->routeIs('guru.chat.*') ? 'active' : '' }}"><i class="bi bi-chat-dots-fill"></i><span>Chat Kelas</span></a></li>

<li class="nav-section">Rekap</li>
<li><a href="{{ route('guru.rekap-nilai') }}" class="nav-link {{ request()->routeIs('guru.rekap-nilai') ? 'active' : '' }}"><i class="bi bi-file-earmark-bar-graph-fill"></i><span>Rekap Nilai</span></a></li>
<li><a href="{{ route('guru.rekap-absensi') }}" class="nav-link {{ request()->routeIs('guru.rekap-absensi') ? 'active' : '' }}"><i class="bi bi-file-earmark-spreadsheet-fill"></i><span>Rekap Absensi</span></a></li>
<li><a href="{{ route('guru.rekap-sikap') }}" class="nav-link {{ request()->routeIs('guru.rekap-sikap') ? 'active' : '' }}"><i class="bi bi-file-earmark-text-fill"></i><span>Rekap Sikap</span></a></li>

@elseif($role == 'siswa')
<li class="nav-section">Belajar</li>
<li><a href="{{ route('siswa.dashboard') }}" class="nav-link {{ request()->routeIs('siswa.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
<li><a href="{{ route('siswa.progress') }}" class="nav-link {{ request()->routeIs('siswa.progress') ? 'active' : '' }}"><i class="bi bi-graph-up-arrow"></i><span>Progress Saya</span></a></li>
<li><a href="{{ route('siswa.materi.index') }}" class="nav-link {{ request()->routeIs('siswa.materi.*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text-fill"></i><span>Materi Saya</span></a></li>
<li><a href="{{ route('siswa.tugas.index') }}" class="nav-link {{ request()->routeIs('siswa.tugas.*') ? 'active' : '' }}"><i class="bi bi-journal-fill"></i><span>Tugas Saya</span></a></li>
<li><a href="{{ route('siswa.nilai.index') }}" class="nav-link {{ request()->routeIs('siswa.nilai.*') ? 'active' : '' }}"><i class="bi bi-bar-chart-fill"></i><span>Nilai Saya</span></a></li>

<li class="nav-section">Kelas</li>
<li><a href="{{ route('siswa.kalender') }}" class="nav-link {{ request()->routeIs('siswa.kalender') ? 'active' : '' }}"><i class="bi bi-calendar3"></i><span>Kalender</span></a></li>
<li><a href="{{ route('siswa.chat.index') }}" class="nav-link {{ request()->routeIs('siswa.chat.*') ? 'active' : '' }}"><i class="bi bi-chat-dots-fill"></i><span>Chat Kelas</span></a></li>

@elseif($role == 'kepala_sekolah')
<li class="nav-section">Ringkasan</li>
<li><a href="{{ route('kepsek.dashboard') }}" class="nav-link {{ request()->routeIs('kepsek.dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
<li><a href="{{ route('kepsek.statistik') }}" class="nav-link {{ request()->routeIs('kepsek.statistik') ? 'active' : '' }}"><i class="bi bi-graph-up-arrow"></i><span>Statistik</span></a></li>

<li class="nav-section">Komunikasi</li>
<li><a href="{{ route('kepsek.kalender') }}" class="nav-link {{ request()->routeIs('kepsek.kalender') ? 'active' : '' }}"><i class="bi bi-calendar3"></i><span>Kalender</span></a></li>
<li><a href="{{ route('kepsek.pengumuman.index') }}" class="nav-link {{ request()->routeIs('kepsek.pengumuman.*') ? 'active' : '' }}"><i class="bi bi-megaphone-fill"></i><span>Pengumuman</span></a></li>

<li class="nav-section">Laporan</li>
<li><a href="{{ route('kepsek.laporan.absensi') }}" class="nav-link {{ request()->routeIs('kepsek.laporan.absensi') ? 'active' : '' }}"><i class="bi bi-clipboard-data-fill"></i><span>Absensi</span></a></li>
<li><a href="{{ route('kepsek.laporan.nilai') }}" class="nav-link {{ request()->routeIs('kepsek.laporan.nilai') ? 'active' : '' }}"><i class="bi bi-bar-chart-fill"></i><span>Nilai</span></a></li>
<li><a href="{{ route('kepsek.laporan.wali-kelas') }}" class="nav-link {{ request()->routeIs('kepsek.laporan.wali-kelas*') ? 'active' : '' }}"><i class="bi bi-person-badge-fill"></i><span>Wali Kelas</span></a></li>
<li><a href="{{ route('kepsek.laporan.rekap-absensi') }}" class="nav-link {{ request()->routeIs('kepsek.laporan.rekap-absensi') ? 'active' : '' }}"><i class="bi bi-file-earmark-bar-graph-fill"></i><span>Rekap Absensi</span></a></li>
<li><a href="{{ route('kepsek.laporan.rekap-tugas') }}" class="nav-link {{ request()->routeIs('kepsek.laporan.rekap-tugas') ? 'active' : '' }}"><i class="bi bi-journal-check"></i><span>Rekap Tugas</span></a></li>
<li><a href="{{ route('kepsek.laporan.rekap-sikap') }}" class="nav-link {{ request()->routeIs('kepsek.laporan.rekap-sikap') ? 'active' : '' }}"><i class="bi bi-heart-fill"></i><span>Rekap Sikap</span></a></li>
@endif
