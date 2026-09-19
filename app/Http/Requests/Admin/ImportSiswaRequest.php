<?php

namespace App\Http\Requests\Admin;

use App\Support\RoleAccess;
use Illuminate\Foundation\Http\FormRequest;

class ImportSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasRole(RoleAccess::ADMIN) || $this->user()?->hasRole(RoleAccess::GURU));
    }

    public function rules(): array
    {
        return [
            'file_siswa' => ['required', 'file', 'mimes:xlsx', 'max:5120'],
        ];
    }
}
