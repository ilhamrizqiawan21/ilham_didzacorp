<?php

namespace App\Http\Requests\Admin;

use App\Support\RoleAccess;
use Illuminate\Foundation\Http\FormRequest;

class StoreKelasMapelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole(RoleAccess::GURU);
    }

    public function rules(): array
    {
        return [
            'kelas_id' => ['required', 'exists:kelas,id'],
            'mapel_id' => ['required', 'exists:mata_pelajaran,id'],
            'guru_id' => ['required', 'exists:users,id'],
            'tahun_ajaran_id' => ['required', 'exists:tahun_ajaran,id'],
            'semester' => ['required', 'in:1,2'],
            'pertemuan_per_minggu' => ['required', 'integer', 'min:1', 'max:6'],
        ];
    }
}
