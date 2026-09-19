<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class StoreBulkSikapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester' => ['required', 'in:1,2'],
            'kelas_mapel_ids' => ['required', 'array', 'min:1'],
            'kelas_mapel_ids.*' => ['integer'],
            'sosial' => ['required', 'array'],
            'sosial.*' => ['array'],
            'sosial.*.*.empati' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sosial.*.*.kerjasama' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sosial.*.*.toleransi' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sosial.*.*.percaya_diri' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sosial.*.*.komunikasi' => ['nullable', 'integer', 'min:1', 'max:5'],
            'spiritual' => ['required', 'array'],
            'spiritual.*' => ['array'],
            'spiritual.*.*.taqwa' => ['nullable', 'integer', 'min:1', 'max:5'],
            'spiritual.*.*.kejujuran' => ['nullable', 'integer', 'min:1', 'max:5'],
            'spiritual.*.*.disiplin' => ['nullable', 'integer', 'min:1', 'max:5'],
            'spiritual.*.*.sabar' => ['nullable', 'integer', 'min:1', 'max:5'],
            'spiritual.*.*.syukur' => ['nullable', 'integer', 'min:1', 'max:5'],
            'spiritual.*.*.tawadhu' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}
