<?php

namespace App\Http\Requests\Admin;

use App\Support\RoleAccess;
use Illuminate\Foundation\Http\FormRequest;

class SiswaFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole(RoleAccess::GURU);
    }

    public function rules(): array
    {
        return [
            'kelas_id' => ['nullable', 'exists:kelas,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:aktif,lulus,keluar'],
        ];
    }
}
