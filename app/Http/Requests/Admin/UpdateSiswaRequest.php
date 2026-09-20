<?php

namespace App\Http\Requests\Admin;

use App\Models\Siswa;
use App\Support\RoleAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole(RoleAccess::GURU);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nis' => Str::upper(trim((string) $this->input('nis'))),
            'nama_lengkap' => trim((string) $this->input('nama_lengkap')),
        ]);
    }

    public function rules(): array
    {
        $siswa = $this->route('siswa');
        $siswaId = $siswa instanceof Siswa ? $siswa->id : $siswa;
        $userId = $siswa instanceof Siswa ? $siswa->user_id : null;

        return [
            'nis' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9._-]+$/', 'unique:siswa,nis,'.$siswaId, 'unique:users,username,'.$userId],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tinggal_kelas' => ['boolean'],
        ];
    }
}
