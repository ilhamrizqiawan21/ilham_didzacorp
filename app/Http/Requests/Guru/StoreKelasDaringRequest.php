<?php

namespace App\Http\Requests\Guru;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKelasDaringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('guru');
    }

    public function rules(): array
    {
        return [
            'kelas_mapel_id' => ['required', 'integer'],
            'judul' => ['required', 'string', 'max:200'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'pelajaran_ke' => ['required', 'integer', 'min:1', 'max:5'],
            'meeting_url' => ['required', 'url', 'max:500'],
            'status' => ['nullable', Rule::in(['terjadwal', 'selesai', 'dibatalkan'])],
        ];
    }
}
