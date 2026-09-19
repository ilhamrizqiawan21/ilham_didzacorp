<?php

namespace App\Http\Requests\Guru;

use App\Models\KelasDaring;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKelasDaringStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $kelasDaring = $this->route('kelasDaring');

        return $kelasDaring instanceof KelasDaring
            && (int) $kelasDaring->guru_id === (int) $this->user()?->id;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['terjadwal', 'selesai', 'dibatalkan'])],
        ];
    }
}
