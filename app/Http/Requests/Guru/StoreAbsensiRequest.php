<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;

class StoreAbsensiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('mengajar', $this->route('kelasMapel'));
    }

    public function rules(): array
    {
        return [
            'bulan' => ['required', 'date_format:Y-m'],
            'absensi' => ['nullable', 'array'],
            'absensi.*' => ['array'],
            'absensi.*.*' => ['nullable', 'in:hadir,sakit,izin,alpha'],
        ];
    }
}
