<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class StoreBulkNilaiRequest extends FormRequest
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
            'nilai' => ['required', 'array'],
            'nilai.*' => ['array'],
            'nilai.*.*.sum1' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai.*.*.sum2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai.*.*.sum3' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai.*.*.sum4' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai.*.*.sts' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai.*.*.sas' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'nilai.*.*.sat' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
