<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class EliminarEppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'epp_id' => 'required|numeric|min:1',
            'motivo' => 'required|string|min:5|max:1000',
        ];
    }
}

