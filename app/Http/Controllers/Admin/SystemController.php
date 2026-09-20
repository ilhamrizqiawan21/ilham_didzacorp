<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\GuruMapel;
use App\Models\Kelas;
use App\Models\LogLogin;
use App\Models\MataPelajaran;
use App\Models\Pengaturan;
use App\Models\SchoolSetting;
use App\Models\SystemError;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Models\WaliKelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

class SystemController extends Controller
{
    public function logLogin(Request $request)
    {
        // Mengecek aktivitas login user
        $query = $this->logLoginQuery($request);

        $logs = $query->paginate(25)
            ->withQueryString()
            ->through(fn (LogLogin $log) => [
                'id' => $log->id,
                'login_time' => optional($log->login_time)->format('d M Y H:i:s'),
                'username' => $log->username,
                'nama_lengkap' => $log->nama_lengkap,
                'role' => $log->role,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
            ]);

        return Inertia::render('Admin/LogLogin/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search']),
            'exportUrl' => route('admin.log-login.export.excel'),
        ]);
    }

    public function exportLogLoginExcel(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
        ]);

        $writer = new Writer;
        $filePath = tempnam(sys_get_temp_dir(), 'log_login_');
        $filename = 'log_login_'.date('Ymd_His').'.xlsx';

        $writer->openToFile($filePath);
        $sheet = $writer->getCurrentSheet();
        $sheet->setColumnWidth(6, 1);
        $sheet->setColumnWidth(20, 2);
        $sheet->setColumnWidth(22, 3);
        $sheet->setColumnWidth(28, 4);
        $sheet->setColumnWidth(18, 5);
        $sheet->setColumnWidth(18, 6);
        $sheet->setColumnWidth(56, 7);

        $styles = $this->excelStyles();
        $writer->addRow(Row::fromValuesWithStyle([school_setting('school_name', 'Nama Sekolah')], $styles['school'], 24));
        $writer->addRow(Row::fromValuesWithStyle(['LOG LOGIN'], $styles['title'], 24));
        $writer->addRow(Row::fromValuesWithStyle(['Tanggal Export', now()->format('d/m/Y H:i')], $styles['meta'], 18));
        $writer->addRow(Row::fromValuesWithStyle(['Filter Pencarian', $request->string('search')->toString() ?: 'Semua data'], $styles['meta'], 18));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValuesWithStyle([
            'No',
            'Waktu Login',
            'Username',
            'Nama',
            'Role',
            'IP Address',
            'User Agent',
        ], $styles['tableHeader'], 24));

        $index = 0;
        foreach ($this->logLoginQuery($request)->cursor() as $log) {
            $writer->addRow(Row::fromValuesWithStyle([
                $index + 1,
                optional($log->login_time)->format('d/m/Y H:i:s'),
                $log->username ?: '-',
                $log->nama_lengkap ?: '-',
                $this->roleLabel($log->role),
                $log->ip_address ?: '-',
                $log->user_agent ?: '-',
            ], $index % 2 === 0 ? $styles['row'] : $styles['alternateRow'], 20));
            $index++;
        }

        $writer->close();

        return response()
            ->download($filePath, $filename)
            ->deleteFileAfterSend(true);
    }

    // Menampilkan riwayat login sistem
    public function logError(Request $request)
    {
        $query = SystemError::orderBy('created_at', 'desc');

        if ($request->filled('level')) {
            $query->where('error_level', $request->level);
        }

        $errors = $query->paginate(25)
            ->withQueryString()
            ->through(fn (SystemError $error) => [
                'id' => $error->id,
                'error_level' => $error->error_level,
                'created_at' => optional($error->created_at)->format('d/m H:i'),
                'message' => $error->message,
                'file' => $error->file,
                'line' => $error->line,
                'url' => $error->url,
            ]);
        $levels = SystemError::select('error_level')->distinct()->pluck('error_level');

        return Inertia::render('Admin/LogError/Index', [
            'errors' => $errors,
            'levels' => $levels,
            'filters' => $request->only(['level']),
        ]);
    }

    // Pengaturan sistem seperti warna tema, nama sekolah, semester aktif, tahun ajaran aktif, dan mode kenaikan kelas
    public function pengaturan()
    {
        $settings = Pengaturan::pluck('value', 'key')->toArray();
        $tahunAjaranAktif = TahunAjaran::getAktif();
        $schoolSetting = SchoolSetting::query()->first() ?: new SchoolSetting(SchoolSetting::fallback());
        $guru = User::whereHas('role', fn ($q) => $q->where('nama_role', 'guru'))->where('is_active', true)->orderBy('id')->first();
        $guruMapelIds = $guru ? GuruMapel::where('guru_id', $guru->id)->pluck('mapel_id')->values() : collect();
        $waliKelas = $guru && $tahunAjaranAktif
            ? WaliKelas::where('guru_id', $guru->id)->where('tahun_ajaran_id', $tahunAjaranAktif->id)->first()
            : null;

        return Inertia::render('Admin/Pengaturan/Index', [
            'settings' => [
                'warna_tema' => $settings['warna_tema'] ?? 'hijau',
                'semester_aktif' => $settings['semester_aktif'] ?? '1',
                'mode_kenaikan' => $settings['mode_kenaikan'] ?? 'manual',
                'penalty_terlambat_poin' => $settings['penalty_terlambat_poin'] ?? '1',
                'whatsapp_template_tugas_terlambat' => $settings['whatsapp_template_tugas_terlambat'] ?? '',
            ],
            'tahunAjaranAktif' => $tahunAjaranAktif ? [
                'id' => $tahunAjaranAktif->id,
                'tahun' => $tahunAjaranAktif->tahun,
            ] : null,
            'schoolSetting' => $this->schoolSettingPayload($schoolSetting),
            'teaching' => [
                'guru' => $guru ? ['id' => $guru->id, 'nama' => $guru->nama_lengkap, 'username' => $guru->username] : null,
                'mapel' => MataPelajaran::orderBy('urutan')->orderBy('nama_mapel')->get(['id', 'kode', 'nama_mapel'])->values(),
                'selected_mapel_ids' => $guruMapelIds,
                'kelas' => Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get(['id', 'tingkat', 'nama_kelas'])->values(),
                'wali_kelas_id' => $waliKelas?->kelas_id,
            ],
            'urls' => [
                'save_system' => route('admin.pengaturan.save'),
                'save_school' => route('admin.school-settings.update'),
                'tahun_ajaran' => route('admin.tahun-ajaran.index'),
                'blocked_ips' => route('admin.blocked-ips'),
                'save_teaching' => route('admin.pengaturan.teaching.save'),
            ],
        ]);
    }

    public function saveTeaching(Request $request)
    {
        $validated = $request->validate([
            'guru_id' => ['required', 'integer', 'exists:users,id'],
            'mapel_ids' => ['array'],
            'mapel_ids.*' => ['integer', 'exists:mata_pelajaran,id'],
            'wali_kelas_id' => ['nullable', 'integer', 'exists:kelas,id'],
        ]);

        $guru = User::whereKey($validated['guru_id'])->whereHas('role', fn ($q) => $q->where('nama_role', 'guru'))->firstOrFail();
        $tahunAjaran = TahunAjaran::getAktif();

        DB::transaction(function () use ($validated, $guru, $tahunAjaran) {
            GuruMapel::where('guru_id', $guru->id)->delete();
            foreach (array_unique(array_map('intval', $validated['mapel_ids'] ?? [])) as $mapelId) {
                GuruMapel::create(['guru_id' => $guru->id, 'mapel_id' => $mapelId]);
            }

            if ($tahunAjaran) {
                $current = WaliKelas::where('guru_id', $guru->id)->where('tahun_ajaran_id', $tahunAjaran->id)->first();
                $requestedClassId = $validated['wali_kelas_id'] ?? null;
                if ($current && (int) $current->kelas_id !== (int) $requestedClassId) {
                    $hasHistory = $current->absensi()->exists() || $current->pertemuan()->exists() || $current->penangananSiswa()->exists();
                    if ($hasHistory) {
                        throw ValidationException::withMessages([
                            'wali_kelas_id' => 'Kelas wali tidak dapat diubah karena sudah memiliki riwayat data.',
                        ]);
                    }
                    $current->delete();
                }
                if ($requestedClassId && (! $current || (int) $current->kelas_id !== (int) $requestedClassId)) {
                    WaliKelas::create([
                        'guru_id' => $guru->id,
                        'kelas_id' => $requestedClassId,
                        'tahun_ajaran_id' => $tahunAjaran->id,
                    ]);
                }
            }
        });

        return back()->with('success', 'Pengaturan guru, mata pelajaran, dan wali kelas berhasil disimpan.');
    }

    // Simpan pengaturan sistem
    public function savePengaturan(Request $request)
    {
        $data = $request->validate([
            'warna_tema' => 'nullable|in:hijau,biru-azure,biru-aqua,indigo,marun',
            'semester_aktif' => 'nullable|in:1,2',
            'mode_kenaikan' => 'nullable|in:manual,auto',
            'penalty_terlambat_poin' => 'nullable|numeric|min:0|max:100',
            'whatsapp_template_tugas_terlambat' => 'nullable|string|max:4000',
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                Pengaturan::updateOrCreate(['key' => $key], ['value' => $value]);
            }
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    // Memblokir IP tertentu agar tidak bisa mengakses sistem
    public function blockedIps(Request $request)
    {
        // Remove stale entries so the page and the middleware use the same
        // definition of an active block.
        BlockedIp::query()->where('blocked_until', '<=', now())->delete();

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:45'],
        ]);

        $query = BlockedIp::query()->active();
        if (filled($validated['search'] ?? null)) {
            $query->where('ip_address', 'like', '%'.addcslashes($validated['search'], '%_\\').'%');
        }

        $ips = $query->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (BlockedIp $ip) => [
                'id' => $ip->id,
                'ip_address' => $ip->ip_address,
                'blocked_until' => optional($ip->blocked_until)->format('d M Y H:i'),
                'is_expired' => $ip->blocked_until ? $ip->blocked_until->isPast() : false,
                'reason' => $ip->reason,
                'created_at' => optional($ip->created_at)->format('d M Y H:i'),
                'unblock_url' => route('admin.blocked-ips.unblock', $ip),
            ]);

        return Inertia::render('Admin/BlockedIps/Index', [
            'ips' => $ips,
            'filters' => ['search' => $validated['search'] ?? ''],
        ]);
    }

    // Membuka blokir IP tertentu agar bisa mengakses sistem kembali
    public function unblockIp(BlockedIp $blockedIp)
    {
        $blockedIp->delete();

        return back()->with('success', 'IP berhasil di-unblock.');
    }

    private function logLoginQuery(Request $request)
    {
        $query = LogLogin::orderBy('login_time', 'desc');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function roleLabel(?string $role): string
    {
        return $role ? str_replace('_', ' ', ucwords($role, '_')) : '-';
    }

    private function schoolSettingPayload(SchoolSetting $setting): array
    {
        return [
            'school_name' => $setting->school_name,
            'school_short_name' => $setting->school_short_name,
            'address' => $setting->address,
            'village' => $setting->village,
            'district' => $setting->district,
            'city' => $setting->city,
            'province' => $setting->province,
            'postal_code' => $setting->postal_code,
            'phone' => $setting->phone,
            'whatsapp' => $setting->whatsapp,
            'email' => $setting->email,
            'website' => $setting->website,
            'npsn' => $setting->npsn,
            'nsm' => $setting->nsm,
            'accreditation' => $setting->accreditation,
            'school_status' => $setting->school_status,
            'principal_name' => $setting->principal_name,
            'principal_nip' => $setting->principal_nip,
            'principal_nuptk' => $setting->principal_nuptk,
            'foundation_name' => $setting->foundation_name,
            'school_year' => $setting->school_year,
            'semester' => $setting->semester,
            'vision' => $setting->vision,
            'mission' => $setting->mission,
            'motto' => $setting->motto,
            'logo_url' => $setting->logo_path ? Storage::url($setting->logo_path) : null,
            'favicon_url' => $setting->favicon_path ? Storage::url($setting->favicon_path) : null,
        ];
    }

    private function excelStyles(): array
    {
        $border = new Border(
            new BorderPart(BorderName::TOP, 'CBD5E1', BorderWidth::THIN),
            new BorderPart(BorderName::RIGHT, 'CBD5E1', BorderWidth::THIN),
            new BorderPart(BorderName::BOTTOM, 'CBD5E1', BorderWidth::THIN),
            new BorderPart(BorderName::LEFT, 'CBD5E1', BorderWidth::THIN),
        );

        $base = (new Style)
            ->withFontName('Arial')
            ->withFontSize(10)
            ->withShouldWrapText(true)
            ->withCellVerticalAlignment(CellVerticalAlignment::CENTER);

        return [
            'school' => $base
                ->withFontBold(true)
                ->withFontSize(14)
                ->withFontColor('0F172A')
                ->withCellAlignment(CellAlignment::CENTER),
            'title' => $base
                ->withFontBold(true)
                ->withFontSize(13)
                ->withFontColor(Color::WHITE)
                ->withBackgroundColor('1D4ED8')
                ->withCellAlignment(CellAlignment::CENTER),
            'meta' => $base
                ->withFontColor('475569')
                ->withBackgroundColor('F8FAFC'),
            'tableHeader' => $base
                ->withFontBold(true)
                ->withFontColor(Color::WHITE)
                ->withBackgroundColor('334155')
                ->withCellAlignment(CellAlignment::CENTER)
                ->withBorder($border),
            'row' => $base
                ->withBackgroundColor(Color::WHITE)
                ->withBorder($border),
            'alternateRow' => $base
                ->withBackgroundColor('F8FAFC')
                ->withBorder($border),
        ];
    }
}
