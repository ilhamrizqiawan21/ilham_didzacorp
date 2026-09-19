<?php

namespace App\Http\Requests\Admin;

use App\Support\RoleAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffUserFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user()?->hasRole(RoleAccess::ADMIN) || $this->user()?->hasRole(RoleAccess::GURU));
    }

    public function rules(): array
    {
        return [
            'role_id' => ['nullable', 'integer', Rule::in(RoleAccess::staffRoleIds())],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
