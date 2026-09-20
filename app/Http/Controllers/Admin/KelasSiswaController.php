<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportSiswaRequest;
use App\Http\Requests\Admin\SiswaFilterRequest;
use App\Http\Requests\Admin\StoreSiswaRequest;
use App\Http\Requests\Admin\UpdateSiswaRequest;
use App\Models\Kelas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use App\Services\SiswaExportService;
use App\Services\SiswaImportService;
use App\Services\SiswaTemplateService;
use App\Support\RoleAccess;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class KelasSiswaController extends Controller
{
    public function index(SiswaFilterRequest $request)
    {
        $kelasList = Kelas::withCount(['siswa' => fn ($query) => $query->where('status', 'aktif')])
            ->orderByRaw("CASE tingkat WHEN 'VII' THEN 1 WHEN 'VIII' THEN 2 WHEN 'IX' THEN 3 ELSE 4 END")
            ->orderBy('nama_kelas')
            ->get();
        // Urutan User berdasarkan NIS/Kode Guru
        $query = Siswa::with(['user', 'kelas'])
            ->whereHas('user')
            ->orderBy('nis');

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nis', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('nama_lengkap', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $siswa = $query->paginate(25)
            ->withQueryString()
            ->through(function (Siswa $siswa) {
                $passwordIsDefault = (bool) $siswa->user?->is_password_default;

                return [
                    'id' => $siswa->id,
                    'nis' => $siswa->nis,
                    'kelas_id' => $siswa->kelas_id,
                    'kelas' => trim(($siswa->kelas?->tingkat ? $siswa->kelas->tingkat.' ' : '').($siswa->kelas?->nama_kelas ?? '')),
                    'status' => $siswa->status,
                    'is_active' => (bool) $siswa->user?->is_active,
                    'tinggal_kelas' => (bool) $siswa->tinggal_kelas,
                    'nama_lengkap' => $siswa->user?->nama_lengkap,
                    'jenis_kelamin' => $siswa->user?->jenis_kelamin,
                    'password_is_default' => $passwordIsDefault,
                    'password_status' => $passwordIsDefault ? 'Masih default' : 'Sudah diubah',
                ];
            });

        return Inertia::render('Admin/KelasSiswa/Index', [
            'kelasList' => $kelasList->map(fn (Kelas $kelas) => [
                'id' => $kelas->id,
                'tingkat' => $kelas->tingkat,
                'nama_kelas' => $kelas->nama_kelas,
                'label' => "{$kelas->tingkat} {$kelas->nama_kelas}",
                'siswa_count' => $kelas->siswa_count,
            ]),
            'siswa' => $siswa,
            'filters' => $request->only(['kelas_id', 'search', 'status']),
            'metrics' => [
                'total_siswa_aktif' => Siswa::where('status', 'aktif')->count(),
                'total_lulus' => Siswa::where('status', 'lulus')->count(),
                'total_keluar' => Siswa::where('status', 'keluar')->count(),
            ],
            'importErrors' => session('import_errors', []),
            'studentPassword' => session('student_password'),
            'templateUrl' => route('admin.kelas-siswa.import.template'),
            'exportUrl' => route('admin.kelas-siswa.export.excel'),
        ]);
    }

    public function exportExcel(SiswaFilterRequest $request, SiswaExportService $exportService)
    {
        $this->ensureAdmin();

        $query = Siswa::with(['user', 'kelas'])
            ->whereHas('user')
            ->orderBy('nis');

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nis', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('nama_lengkap', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()
            ->download($exportService->export($query), 'export_siswa_'.date('Ymd_His').'.xlsx')
            ->deleteFileAfterSend(true);
    }

    public function downloadTemplate(SiswaTemplateService $templateService)
    {
        $this->ensureAdmin();

        return response()
            ->download($templateService->createTemplateFile(), SiswaTemplateService::FILENAME)
            ->deleteFileAfterSend(true);
    }

    public function importSiswa(ImportSiswaRequest $request, SiswaImportService $importService)
    {
        $this->ensureAdmin();

        $result = $importService->import($request->file('file_siswa')->getRealPath());

        if ($result['errors'] !== []) {
            return back()->with('import_errors', $result['errors']);
        }

        return back()->with('success', $result['imported'].' siswa berhasil diimport.');
    }

    // Save new Siswa
    public function storeSiswa(StoreSiswaRequest $request)
    {
        $this->ensureAdmin();
        $validated = $request->validated();

        try {
            $created = DB::transaction(function () use ($validated) {
                $siswaRoleId = Role::where('nama_role', 'siswa')->value('id');
                $password = $this->generateInitialPassword();

                if (! $siswaRoleId) {
                    throw new \RuntimeException('Role siswa belum tersedia.');
                }

                // Buat user dulu
                $user = User::create([
                    'username' => $validated['nis'],
                    'password' => Hash::make($password),
                    'is_password_default' => true,
                    'nama_lengkap' => $validated['nama_lengkap'],
                    'nip_nis' => $validated['nis'],
                    'role_id' => $siswaRoleId,
                    'jenis_kelamin' => $validated['jenis_kelamin'],
                    'is_active' => true,
                ]);

                // Buat siswa
                Siswa::create([
                    'user_id' => $user->id,
                    'nis' => $validated['nis'],
                    'kelas_id' => $validated['kelas_id'],
                    'status' => 'aktif',
                ]);

                return compact('user', 'password');
            });

            return back()->with(
                'success',
                "Siswa {$validated['nama_lengkap']} berhasil ditambahkan."
            )->with('student_password', [
                'title' => 'Password awal siswa',
                'name' => $validated['nama_lengkap'],
                'username' => $created['user']->username,
                'password' => $created['password'],
            ]);
        } catch (UniqueConstraintViolationException $e) {
            return back()
                ->withInput()
                ->with('error', 'Data sudah ada di database. Silakan periksa kembali NIS atau username yang dimasukkan.');
        }
    }

    // Edit Siswa
    public function updateSiswa(UpdateSiswaRequest $request, Siswa $siswa)
    {
        $this->ensureAdmin();
        $validated = $request->validated();

        DB::transaction(function () use ($siswa, $validated) {
            $user = $siswa->user;
            abort_unless($user, 404);

            $siswa->update([
                'nis' => $validated['nis'],
                'kelas_id' => $validated['kelas_id'],
                'tinggal_kelas' => $validated['tinggal_kelas'] ?? false,
            ]);

            $user->update([
                'username' => $validated['nis'],
                'nip_nis' => $validated['nis'],
                'nama_lengkap' => $validated['nama_lengkap'],
                'jenis_kelamin' => $validated['jenis_kelamin'],
            ]);
        });

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    // Reset password ke password default.
    public function resetPassword(Siswa $siswa)
    {
        $this->ensureAdmin();

        $user = $siswa->user;
        abort_unless($user, 404);
        $password = $this->generateInitialPassword();

        DB::transaction(fn () => $user->update([
            'password' => Hash::make($password),
            'is_password_default' => true,
        ]));

        return back()
            ->with('success', 'Password siswa berhasil direset.')
            ->with('student_password', [
                'title' => 'Password baru siswa',
                'name' => $user->nama_lengkap,
                'username' => $user->username,
                'password' => $password,
            ]);
    }

    // Delete Siswa beserta Usernya
    public function destroySiswa(Siswa $siswa)
    {
        $this->ensureAdmin();

        $siswa->loadMissing('user');
        abort_unless($siswa->user, 404);

        if ($siswa->absensi()->exists()
            || $siswa->pengumpulanTugas()->exists()
            || $siswa->nilaiAkhir()->exists()
            || $siswa->sikapSosial()->exists()
            || $siswa->sikapSpiritual()->exists()) {
            return back()->with('error', 'Siswa tidak dapat dihapus karena sudah memiliki riwayat akademik. Ubah status siswa menjadi keluar atau lulus.');
        }

        $nama = $siswa->user->nama_lengkap;

        DB::transaction(function () use ($siswa) {
            $siswa->delete();
            $siswa->user->delete();
        });

        return back()->with('success', "Siswa {$nama} berhasil dihapus.");
    }

    // Tampilkan daftar siswa yang sudah lulus
    public function luluskanKelas(Kelas $kelas)
    {
        $this->ensureAdmin();

        if ($kelas->tingkat !== 'IX') {
            return back()->with('error', 'Hanya kelas IX yang bisa diluluskan.');
        }

        $count = DB::transaction(function () use ($kelas) {
            $siswa = Siswa::where('kelas_id', $kelas->id)
                ->where('status', 'aktif')
                ->get(['id', 'user_id']);

            if ($siswa->isEmpty()) {
                return 0;
            }

            Siswa::whereIn('id', $siswa->pluck('id'))->update(['status' => 'lulus']);
            User::whereIn('id', $siswa->pluck('user_id')->filter())->update(['is_active' => false]);

            return $siswa->count();
        });

        return back()->with('success', "{$count} siswa kelas {$kelas->nama_kelas} berhasil diluluskan.");
    }

    private function generateInitialPassword(): string
    {
        return User::DEFAULT_PASSWORD;
    }

    private function ensureAdmin(): void
    {
        $user = request()->user();

        if (! $user) {
            throw new AuthenticationException;
        }

        abort_unless($user->hasRole(RoleAccess::GURU), 403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
