<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfilController extends Controller
{
    public function edit()
    {
        $user = Auth::user()->load('siswa.kelas');

        return Inertia::render('Siswa/Profil', [
            'profile' => [
                'nis' => $user->siswa?->nis ?? '-',
                'nama_lengkap' => $user->nama_lengkap,
                'username' => $user->username,
                'kelas' => trim(($user->siswa?->kelas?->tingkat ?? '') . ' ' . ($user->siswa?->kelas?->nama_kelas ?? '')) ?: '-',
                'status' => $user->siswa?->status ?? '-',
            ],
            'updateUrl' => route('siswa.profil.update'),
        ]);
    }
    //Update username dan password siswa
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
            'is_password_default' => false,
        ]);

        return back()->with('success', 'Password berhasil diperbarui.');
    }
}
