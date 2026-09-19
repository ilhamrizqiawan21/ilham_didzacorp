<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Support\RoleAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasRole(RoleAccess::ADMIN) || $this->user()?->hasRole(RoleAccess::GURU));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => trim((string) $this->input('username')),
            'nama_lengkap' => trim((string) $this->input('nama_lengkap')),
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
            'nip_nis' => $this->filled('nip_nis') ? trim((string) $this->input('nip_nis')) : null,
        ]);
    }

    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : $user;

        return [
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:users,username,'.$userId],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$userId],
            'role_id' => ['required', 'integer', Rule::in(RoleAccess::staffRoleIds())],
            'nip_nis' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9.\/_-]+$/', 'unique:users,nip_nis,'.$userId],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_active' => ['boolean'],
        ];
    }
}
