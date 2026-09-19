<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class RekapAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kelas_mapel_id' => ['nullable', 'integer'],
            'mode' => ['nullable', 'in:bulanan,keseluruhan'],
            'bulan' => ['nullable', 'date_format:Y-m'],
        ];
    }
}
