<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfilController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        return Inertia::render('Guru/Profil', [
            'profile' => [
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'nip_nis' => $user->nip_nis,
                'role_label' => 'Guru',
                'is_active' => $user->is_active,
            ],
            'updateUrl' => route('guru.profil.update'),
        ]);
    }
    //Edit profil siswa atau guru
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'nip_nis' => 'nullable|string|max:50',
            'current_password' => 'required_with:password|current_password',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $data = [
            'nama_lengkap' => $validated['nama_lengkap'],
            'username' => $validated['username'],
            'nip_nis' => $validated['nip_nis'],
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($validated['password']);
            $data['is_password_default'] = false;
        }

        $user->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
