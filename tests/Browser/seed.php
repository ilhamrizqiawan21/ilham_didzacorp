<?php

use App\Models\Absensi;
use App\Models\JadwalMengajar;
use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\MataPelajaran;
use App\Models\NilaiAkhir;
use App\Models\Notifikasi;
use App\Models\Pengaturan;
use App\Models\PengumpulanTugas;
use App\Models\Pengumuman;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Tugas;
use App\Models\User;
use App\Models\WaliKelas;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = config('database.connections.sqlite.database');
if (! $app->environment('testing') || config('database.default') !== 'sqlite'
    || ! str_starts_with($database, sys_get_temp_dir().'/lms-browser-')) {
    throw new RuntimeException('Browser fixtures require an isolated temporary SQLite database.');
}

Artisan::call('migrate', ['--force' => true]);
$users = [];
foreach (['admin', 'guru', 'siswa', 'kepala_sekolah'] as $roleName) {
    $role = Role::create(['nama_role' => $roleName]);
    $users[$roleName] = User::create([
        'username' => 'browser-'.$roleName,
        'nama_lengkap' => 'Pengguna Uji '.$roleName,
        'email' => $roleName.'@browser.test',
        'password' => Hash::make('browser-test-password'),
        'role_id' => $role->id,
        'is_active' => true,
        'is_password_default' => false,
    ]);
    Notifikasi::create([
        'user_id' => $users[$roleName]->id, 'tipe' => 'pengumuman_baru',
        'judul' => 'Notifikasi Uji', 'pesan' => 'Pesan uji migrasi', 'is_read' => false,
    ]);
}
$year = TahunAjaran::create(['tahun' => '2026/2027', 'is_active' => true]);
Pengaturan::updateOrCreate(['key' => 'semester_aktif'], ['value' => '1']);
$class = Kelas::create(['tingkat' => 'VII', 'nama_kelas' => 'VII-A']);
$subject = MataPelajaran::create(['kode' => 'TS', 'nama_mapel' => 'Matematika', 'urutan' => 1]);
$course = KelasMapel::create([
    'kelas_id' => $class->id, 'mapel_id' => $subject->id, 'guru_id' => $users['guru']->id,
    'tahun_ajaran_id' => $year->id, 'semester' => '1', 'pertemuan_per_minggu' => 1,
]);
$student = Siswa::create([
    'user_id' => $users['siswa']->id, 'nis' => '9001', 'kelas_id' => $class->id, 'status' => 'aktif',
    'nomor_whatsapp' => '6281234567890', 'whatsapp_opt_in' => true, 'whatsapp_opted_in_at' => now(),
]);
foreach ([2, 3] as $number) {
    $additionalUser = User::create([
        'username' => 'browser-siswa-'.$number,
        'nama_lengkap' => 'Siswa Tambahan '.$number,
        'email' => 'siswa'.$number.'@browser.test',
        'password' => Hash::make('browser-test-password'),
        'role_id' => $users['siswa']->role_id,
        'is_active' => true,
        'is_password_default' => false,
    ]);
    Siswa::create([
        'user_id' => $additionalUser->id, 'nis' => '900'.$number,
        'kelas_id' => $class->id, 'status' => 'aktif',
    ]);
}
WaliKelas::create(['kelas_id' => $class->id, 'guru_id' => $users['guru']->id, 'tahun_ajaran_id' => $year->id]);
JadwalMengajar::create([
    'guru_id' => $users['guru']->id, 'kelas_id' => $class->id,
    'kelas_mapel_id' => $course->id, 'hari' => 1, 'pelajaran_ke' => 1,
]);
NilaiAkhir::create([
    'siswa_id' => $student->id, 'kelas_mapel_id' => $course->id, 'tahun_ajaran_id' => $year->id,
    'semester' => '1', 'sum1' => 80, 'sum2' => 90,
]);
Absensi::create([
    'siswa_id' => $student->id, 'kelas_mapel_id' => $course->id,
    'tanggal' => now()->startOfMonth()->next('Monday')->toDateString(), 'status' => 'hadir',
]);
$task = Tugas::create([
    'kelas_mapel_id' => $course->id, 'judul' => 'Tugas Uji TypeScript',
    'deskripsi' => 'Latihan matematika', 'batas_waktu' => now()->addWeek(), 'kategori_nilai' => 'NH',
]);
PengumpulanTugas::create(['tugas_id' => $task->id, 'siswa_id' => $student->id, 'status' => 'belum']);
Pengumuman::create([
    'judul' => 'Pengumuman Uji', 'isi' => 'Informasi sekolah untuk pengujian.',
    'target' => 'semua', 'created_by' => $users['admin']->id, 'is_public_login' => true,
]);
echo "Browser fixtures ready.\n";
