<?php

namespace App\Http\Controllers;

use App\Support\WhatsAppPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AccountSettingsController extends Controller
{
    public function edit()
    {
        $user = Auth::user()->loadMissing(['role', 'siswa.kelas']);
        $role = $user->role?->nama_role;

        return Inertia::render('Account/Pengaturan', [
            'profile' => [
                'username' => $user->username,
                'email' => $user->email ?: '-',
                'nama_lengkap' => $user->nama_lengkap,
                'nip_nis' => $user->nip_nis ?: '-',
                'jenis_kelamin' => $this->genderLabel($user->jenis_kelamin),
                'role' => $role,
                'role_label' => $this->roleLabel($role),
                'foto_url' => $user->foto ? Storage::disk('public')->url($user->foto) : null,
                'is_active' => (bool) $user->is_active,
                'is_password_default' => (bool) $user->is_password_default,
                'created_at' => $user->created_at ? (string) $user->created_at : '-',
                'siswa' => $user->siswa ? [
                    'nis' => $user->siswa->nis ?: '-',
                    'kelas' => trim(($user->siswa->kelas?->tingkat ?? '').' '.($user->siswa->kelas?->nama_kelas ?? '')) ?: '-',
                    'angkatan' => $user->siswa->angkatan ?: '-',
                    'status' => $user->siswa->status ?: '-',
                    'tinggal_kelas' => (bool) $user->siswa->tinggal_kelas,
                    'nomor_whatsapp' => $user->siswa->nomor_whatsapp,
                    'whatsapp_opt_in' => (bool) $user->siswa->whatsapp_opt_in,
                    'phone_required' => config('security.require_student_phone', false)
                        && (! WhatsAppPhone::isValid($user->siswa->nomor_whatsapp) || ! $user->siswa->whatsapp_opt_in),
                ] : null,
            ],
            'updateUrl' => $this->updateRoute($role),
            'avatarUpdateUrl' => $this->avatarUpdateRoute($role),
            'avatarDeleteUrl' => $this->avatarDeleteRoute($role),
            'phoneUpdateUrl' => $role === 'siswa' ? route('siswa.pengaturan.telepon') : null,
        ]);
    }

    public function updateStudentPhone(Request $request)
    {
        abort_unless($request->user()->hasRole('siswa'), 403);
        $siswa = $request->user()->siswa;
        abort_unless($siswa, 403, 'Data siswa belum tersedia. Hubungi administrator sekolah.');

        $validated = $request->validate([
            'nomor_whatsapp' => ['required', 'string', 'max:32'],
            'whatsapp_opt_in' => ['required', 'accepted'],
        ], [
            'nomor_whatsapp.required' => 'Nomor telepon wajib diisi.',
            'nomor_whatsapp.max' => 'Nomor telepon terlalu panjang.',
            'whatsapp_opt_in.required' => 'Persetujuan menerima informasi dan pengingat melalui WhatsApp wajib diberikan.',
            'whatsapp_opt_in.accepted' => 'Persetujuan menerima informasi dan pengingat melalui WhatsApp wajib diberikan.',
        ]);
        $number = WhatsAppPhone::normalize($validated['nomor_whatsapp']);
        if ($number === null) {
            throw ValidationException::withMessages([
                'nomor_whatsapp' => 'Masukkan nomor seluler Indonesia yang valid (08, 628, atau +628).',
            ]);
        }

        $consentChanged = ! $siswa->whatsapp_opt_in || $siswa->nomor_whatsapp !== $number;
        $siswa->update([
            'nomor_whatsapp' => $number,
            'whatsapp_opt_in' => true,
            'whatsapp_opted_in_at' => $consentChanged ? now() : ($siswa->whatsapp_opted_in_at ?? now()),
        ]);

        return redirect()->route('siswa.pengaturan')->with('success', 'Nomor telepon berhasil disimpan.');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'confirmed',
                Password::min(10)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'is_password_default' => false,
        ]);

        return back()->with('success', 'Password berhasil diperbarui.');
    }

    public function uploadAvatar(Request $request)
    {
        $validated = $request->validate([
            'foto' => 'required|file|mimes:jpg,jpeg,png,webp|extensions:jpg,jpeg,png,webp|max:2048',
        ], [
            'foto.extensions' => 'Foto harus berupa file .jpg, .jpeg, .png, atau .webp.',
            'foto.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        $user = $request->user();
        $oldPath = $user->foto;
        $path = $validated['foto']->store('avatars/'.$user->id, 'public');

        $user->update(['foto' => $path]);

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function deleteAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->foto) {
            Storage::disk('public')->delete($user->foto);
            $user->update(['foto' => null]);
        }

        return back()->with('success', 'Foto profil berhasil dihapus.');
    }

    private function updateRoute(?string $role): string
    {
        return match ($role) {
            'admin' => route('admin.pengaturan-akun.update'),
            'guru' => route('guru.pengaturan.update'),
            'siswa' => route('siswa.pengaturan.update'),
            'kepala_sekolah' => route('kepsek.pengaturan.update'),
            default => url()->current(),
        };
    }

    private function avatarUpdateRoute(?string $role): string
    {
        return match ($role) {
            'admin' => route('admin.pengaturan-akun.foto'),
            'guru' => route('guru.pengaturan.foto'),
            'siswa' => route('siswa.pengaturan.foto'),
            'kepala_sekolah' => route('kepsek.pengaturan.foto'),
            default => url()->current(),
        };
    }

    private function avatarDeleteRoute(?string $role): string
    {
        return match ($role) {
            'admin' => route('admin.pengaturan-akun.foto.delete'),
            'guru' => route('guru.pengaturan.foto.delete'),
            'siswa' => route('siswa.pengaturan.foto.delete'),
            'kepala_sekolah' => route('kepsek.pengaturan.foto.delete'),
            default => url()->current(),
        };
    }

    private function roleLabel(?string $role): string
    {
        return match ($role) {
            'admin' => 'Admin',
            'guru' => 'Guru',
            'siswa' => 'Siswa',
            'kepala_sekolah' => 'Kepala Sekolah',
            default => $role ?: '-',
        };
    }

    private function genderLabel(?string $gender): string
    {
        return match ($gender) {
            'L', 'l', 'laki-laki', 'Laki-laki', 'Laki-Laki' => 'Laki-laki',
            'P', 'p', 'perempuan', 'Perempuan' => 'Perempuan',
            default => $gender ?: '-',
        };
    }
}
