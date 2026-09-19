<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class StoreNilaiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('mengajar', $this->route('kelasMapel'));
    }

    public function rules(): array
    {
        return [
            'semester' => ['required', 'in:1,2'],
            'nilai' => ['required', 'array'],
            'nilai.*.sum1' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai.*.sum2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai.*.sum3' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai.*.sum4' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai.*.sts' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai.*.sas' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai.*.sat' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
