<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class GradeTugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('mengajar', $this->route('kelasMapel'));
    }

    public function rules(): array
    {
        return [
            'nilai' => ['nullable', 'required_without:catatan', 'numeric', 'min:0', 'max:100'],
            'catatan' => ['nullable', 'required_without:nilai', 'string', 'max:500'],
        ];
    }
}
