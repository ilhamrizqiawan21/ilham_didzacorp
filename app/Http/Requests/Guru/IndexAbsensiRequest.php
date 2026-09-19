<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class IndexAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bulan' => ['nullable', 'date_format:Y-m'],
            'kelas_mapel_id' => ['nullable', 'integer'],
            'siswa_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
