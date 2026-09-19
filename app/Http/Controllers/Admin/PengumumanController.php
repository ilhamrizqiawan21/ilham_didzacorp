<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\KelasMapel;
use App\Models\Notifikasi;
use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PengumumanController extends Controller
{
    public function index()
    {
        $query = Pengumuman::with(['creator','kelasMapel.kelas','kelasMapel.mataPelajaran'])->orderByDesc('created_at');
        if (Auth::user()->isGuru()) {
            $guruKelasIds = KelasMapel::where('guru_id', Auth::id())->pluck('kelas_id')->unique()->values();
            $query->where(function ($q) use ($guruKelasIds) {
                $q->whereIn('target', ['semua', 'guru'])
                    ->orWhere('created_by', Auth::id())
                    ->orWhere(function ($q) use ($guruKelasIds) {
                        $q->where('target', 'kelas_mapel')->where(function ($q) use ($guruKelasIds) {
                            $q->whereIn('kelas_mapel_id', KelasMapel::where('guru_id', Auth::id())->select('id'));
                            foreach ($guruKelasIds as $id) $q->orWhere('target_kelas', 'like', '%\"'.$id.'\"%');
                        });
                    });
            });
        }
        if (Auth::user()->role?->nama_role === 'kepala_sekolah') {
            $query->where(fn ($q) => $q->whereIn('target', ['semua', 'guru'])->orWhere('created_by', Auth::id()));
        }

        $pengumuman = $query->paginate(15)->withQueryString();
        $pengumuman->through(function (Pengumuman $item) {
            $prefix = $this->routePrefix();
            $item->can_edit = Auth::user()->isAdmin() || (Auth::user()->isGuru() && (int) $item->created_by === (int) Auth::id());
            $item->can_delete = $item->can_edit;
            $item->update_url = route($prefix.'.update', $item);
            $item->delete_url = route($prefix.'.destroy', $item);
            $item->show_url = route($prefix.'.show', $item);
            $item->target_kelas_ids = $item->targetKelasIds();
            $item->attachment = $item->public_file_path ? [
                'name' => $item->public_file_name ?: basename($item->public_file_path),
                'size' => $item->public_file_size,
                'url' => $item->is_public_login ? route('public-pengumuman.attachment', $item) : null,
            ] : null;
            return $item;
        });

        $kelas = Kelas::orderBy('tingkat')->orderBy('nama_kelas')->get();
        $kelasMapel = KelasMapel::with(['kelas','mataPelajaran'])
            ->when(Auth::user()->isGuru(), fn ($q) => $q->where('guru_id', Auth::id()))
            ->orderBy('kelas_id')->get();
        $targetKelasOptions = Auth::user()->isGuru()
            ? $kelasMapel->pluck('kelas')->filter()->unique('id')->sortBy(fn (Kelas $k) => $k->tingkat.' '.$k->nama_kelas)->values()
            : $kelas;

        $role = Auth::user()->role?->nama_role;
        return match ($role) {
            'admin', 'guru' => Inertia::render('Admin/Pengumuman/Index', compact('pengumuman','kelas','kelasMapel','targetKelasOptions') + [
                'routePrefix' => $this->routePrefix(),
                'storeUrl' => route($this->routePrefix().'.store'),
            ]),
            'kepala_sekolah' => Inertia::render('Kepsek/Pengumuman/Index', compact('pengumuman') + ['routePrefix' => $this->routePrefix()]),
            default => abort(403),
        };
    }

    public function show(Pengumuman $pengumuman)
    {
        $role = Auth::user()->role?->nama_role;
        abort_unless($this->canView($pengumuman, $role), 403);
        $pengumuman->loadMissing(['creator','kelasMapel.kelas','kelasMapel.mataPelajaran']);
        $targetKelasLabels = Kelas::whereIn('id', $pengumuman->targetKelasIds())
            ->orderBy('tingkat')->orderBy('nama_kelas')->get()
            ->map(fn (Kelas $k) => trim($k->tingkat.' '.$k->nama_kelas))->values();
        $pengumuman->attachment = $pengumuman->public_file_path ? [
            'name' => $pengumuman->public_file_name ?: basename($pengumuman->public_file_path),
            'size' => $pengumuman->public_file_size,
            'url' => $pengumuman->is_public_login ? route('public-pengumuman.attachment', $pengumuman) : null,
        ] : null;

        return match ($role) {
            'admin', 'guru' => Inertia::render('Admin/Pengumuman/Show', compact('pengumuman','targetKelasLabels') + [
                'backUrl' => route($this->routePrefix().'.index'),
            ]),
            'kepala_sekolah' => Inertia::render('Kepsek/Pengumuman/Show', compact('pengumuman','targetKelasLabels') + [
                'backUrl' => route($this->routePrefix().'.index'),
            ]),
            default => abort(403),
        };
    }

    public function store(Request $request)
    {
        $role = Auth::user()->role?->nama_role;
        $allowed = match ($role) {
            'guru' => ['kelas_mapel'],
            'admin' => ['semua','guru','siswa','kelas_mapel'],
            default => [],
        };
        abort_unless($allowed !== [], 403);
        $v = $request->validate([
            'judul' => 'required|string|max:200', 'isi' => 'required|string',
            'target' => ['required', Rule::in($allowed)],
            'target_kelas_ids' => 'nullable|required_if:target,kelas_mapel|array',
            'target_kelas_ids.*' => 'integer|exists:kelas,id',
            'is_public_login' => 'nullable|boolean',
            'public_file' => 'nullable|file|extensions:pdf,jpg,jpeg,png,webp,xls,xlsx,doc,docx|max:5120',
        ]);
        $v = $this->prepareTarget($v);
        $v['is_public_login'] = $request->boolean('is_public_login');
        $v['created_by'] = Auth::id();
        $this->attachPublicFile($request, $v);
        $pengumuman = Pengumuman::create($v);
        $this->notifyRecipients($pengumuman);
        return redirect()->route($this->routePrefix().'.index')->with('success', 'Pengumuman berhasil dipublikasikan.');
    }

    public function update(Request $request, Pengumuman $pengumuman)
    {
        $role = Auth::user()->role?->nama_role;
        abort_unless($role === 'admin' || ($role === 'guru' && (int) $pengumuman->created_by === (int) Auth::id()), 403);
        $allowed = $role === 'guru' ? ['kelas_mapel'] : ['semua','guru','siswa','kelas_mapel'];
        $v = $request->validate([
            'judul' => 'required|string|max:200', 'isi' => 'required|string',
            'target' => ['required', Rule::in($allowed)],
            'target_kelas_ids' => 'nullable|required_if:target,kelas_mapel|array',
            'target_kelas_ids.*' => 'integer|exists:kelas,id',
            'is_public_login' => 'nullable|boolean',
            'public_file' => 'nullable|file|extensions:pdf,jpg,jpeg,png,webp,xls,xlsx,doc,docx|max:5120',
            'remove_public_file' => 'nullable|boolean',
        ]);
        $v = $this->prepareTarget($v);
        $v['is_public_login'] = $request->boolean('is_public_login');

        if ($request->boolean('remove_public_file') || $request->hasFile('public_file')) {
            $this->deletePublicFile($pengumuman);
            $v['public_file_name'] = null;
            $v['public_file_path'] = null;
            $v['public_file_mime'] = null;
            $v['public_file_size'] = null;
        }

        $this->attachPublicFile($request, $v);
        $pengumuman->update($v);
        return redirect()->route($this->routePrefix().'.index')->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Pengumuman $pengumuman)
    {
        $role = Auth::user()->role?->nama_role;
        abort_unless($role === 'admin' || ($role === 'guru' && (int) $pengumuman->created_by === (int) Auth::id()), 403);
        $this->deletePublicFile($pengumuman);
        $pengumuman->delete();
        return redirect()->route($this->routePrefix().'.index')->with('success', 'Pengumuman berhasil dihapus.');
    }

    private function prepareTarget(array $v): array
    {
        if ($v['target'] === 'kelas_mapel') {
            $ids = collect($v['target_kelas_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            if ($ids->isEmpty()) throw ValidationException::withMessages(['target_kelas_ids' => 'Pilih minimal satu kelas tujuan.']);
            if (Auth::user()->isGuru()) {
                $allowed = KelasMapel::where('guru_id', Auth::id())->whereIn('kelas_id', $ids)->pluck('kelas_id')->unique();
                abort_unless($ids->diff($allowed)->isEmpty(), 403);
            }
            $v['target_kelas'] = $ids->map(fn ($id) => (string) $id)->values()->toJson();
            $v['kelas_mapel_id'] = KelasMapel::whereIn('kelas_id', $ids)->value('id');
        } else {
            $v['target_kelas'] = null;
            $v['kelas_mapel_id'] = null;
        }
        unset($v['target_kelas_ids'], $v['public_file'], $v['remove_public_file']);
        return $v;
    }

    private function attachPublicFile(Request $request, array &$data): void
    {
        if (! $request->hasFile('public_file')) {
            return;
        }

        $file = $request->file('public_file');
        $data['public_file_name'] = $file->getClientOriginalName();
        $data['public_file_path'] = $file->store('pengumuman-public/' . Auth::id(), 'local');
        $data['public_file_mime'] = $file->getClientMimeType();
        $data['public_file_size'] = $file->getSize();
    }

    private function deletePublicFile(Pengumuman $pengumuman): void
    {
        if ($pengumuman->public_file_path) {
            Storage::disk('local')->delete($pengumuman->public_file_path);
        }
    }

    private function routePrefix(): string
    {
        return match (Auth::user()->role?->nama_role) {
            'guru' => 'guru.pengumuman', 'kepala_sekolah' => 'kepsek.pengumuman', default => 'admin.pengumuman',
        };
    }

    private function canView(Pengumuman $p, ?string $role): bool
    {
        if ($role === 'admin') return true;
        if ($role === 'kepala_sekolah') return in_array($p->target, ['semua','guru'], true) || (int) $p->created_by === (int) Auth::id();
        if ($role === 'guru') {
            if (in_array($p->target, ['semua','guru'], true) || (int) $p->created_by === (int) Auth::id()) return true;
            return $p->target === 'kelas_mapel' && KelasMapel::whereIn('kelas_id', $p->targetKelasIds())->where('guru_id', Auth::id())->exists();
        }
        return false;
    }

    private function notifyRecipients(Pengumuman $pengumuman): void
    {
        $query = User::query()->where('is_active', true)->where('id', '!=', Auth::id());
        $target = $pengumuman->target;
        if ($target === 'guru') $query->whereHas('role', fn ($q) => $q->where('nama_role', 'guru'));
        elseif ($target === 'siswa') $query->whereHas('role', fn ($q) => $q->where('nama_role', 'siswa'));
        elseif ($target === 'kelas_mapel') $query->whereHas('role', fn ($q) => $q->where('nama_role', 'siswa'))->whereHas('siswa', fn ($q) => $q->whereIn('kelas_id', $pengumuman->targetKelasIds())->where('status', 'aktif'));
        else $query->whereHas('role', fn ($q) => $q->whereIn('nama_role', ['admin','guru','siswa','kepala_sekolah']));
        foreach ($query->with('role')->get(['id','role_id']) as $user) {
            Notifikasi::create(['user_id'=>$user->id,'tipe'=>'pengumuman_baru','judul'=>'Pengumuman baru','pesan'=>$pengumuman->judul,'link'=>$this->notificationLinkForUser($user,$pengumuman)]);
        }
    }

    private function notificationLinkForUser(User $user, Pengumuman $pengumuman): string
    {
        return match ($user->role?->nama_role) {
            'siswa' => route('siswa.pengumuman.show', $pengumuman), 'guru' => route('guru.pengumuman.show', $pengumuman),
            'kepala_sekolah' => route('kepsek.pengumuman.show', $pengumuman), default => route('admin.pengumuman.show', $pengumuman),
        };
    }
}
