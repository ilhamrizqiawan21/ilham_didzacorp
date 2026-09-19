<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class StoreBulkMateriRequest extends FormRequest
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
            'file_materi' => ['required', 'file', 'extensions:jpg,jpeg,pdf', 'max:5120'],
        ];
    }
}
