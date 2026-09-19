<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class StoreSikapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('mengajar', $this->route('kelasMapel'));
    }

    public function rules(): array
    {
        return [
            'semester' => ['required', 'in:1,2'],
            'sosial' => ['required', 'array'],
            'sosial.*.empati' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sosial.*.kerjasama' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sosial.*.toleransi' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sosial.*.percaya_diri' => ['nullable', 'integer', 'min:1', 'max:5'],
            'sosial.*.komunikasi' => ['nullable', 'integer', 'min:1', 'max:5'],
            'spiritual' => ['required', 'array'],
            'spiritual.*.taqwa' => ['nullable', 'integer', 'min:1', 'max:5'],
            'spiritual.*.kejujuran' => ['nullable', 'integer', 'min:1', 'max:5'],
            'spiritual.*.disiplin' => ['nullable', 'integer', 'min:1', 'max:5'],
            'spiritual.*.sabar' => ['nullable', 'integer', 'min:1', 'max:5'],
            'spiritual.*.syukur' => ['nullable', 'integer', 'min:1', 'max:5'],
            'spiritual.*.tawadhu' => ['nullable', 'integer', 'min:1', 'max:5'],
        ];
    }
}
