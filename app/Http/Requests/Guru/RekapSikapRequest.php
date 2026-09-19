<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class RekapSikapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester' => ['nullable', 'in:1,2'],
            'kelas_mapel_id' => ['nullable', 'integer'],
        ];
    }
}
