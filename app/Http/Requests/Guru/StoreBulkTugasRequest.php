<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class StoreBulkTugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kelas_mapel_ids' => ['required', 'array', 'min:1'],
            'kelas_mapel_ids.*' => ['integer'],
            'judul' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string'],
            'batas_waktu' => ['required', 'date', 'after_or_equal:today'],
        ];
    }
}
