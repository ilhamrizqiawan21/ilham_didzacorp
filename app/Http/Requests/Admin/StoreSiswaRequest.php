<?php

namespace App\Http\Requests\Admin;

use App\Support\RoleAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasRole(RoleAccess::ADMIN) || $this->user()?->hasRole(RoleAccess::GURU));
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
        return [
            'nis' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9._-]+$/', 'unique:siswa,nis', 'unique:users,username'],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'jenis_kelamin' => ['required', 'in:L,P'],
        ];
    }

    public function messages(): array
    {
        return [
            'nis.unique' => 'NIS sudah digunakan oleh siswa lain.',
        ];
    }
}
