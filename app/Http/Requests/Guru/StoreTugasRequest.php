<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class StoreTugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('mengajar', $this->route('kelasMapel'));
    }

    public function rules(): array
    {
        return [
            'judul' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string'],
            'batas_waktu' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
