<?php

namespace App\Http\Requests\Guru;

use App\Support\UploadRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreMateriRequest extends FormRequest
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
            'file_materi' => UploadRules::document(),
        ];
    }
}
