<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class AnularPedidoNovedadAsesorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'novedad' => 'required|string|min:10|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'novedad.required' => 'La novedad es obligatoria',
            'novedad.min' => 'La novedad debe tener al menos 10 caracteres',
            'novedad.max' => 'La novedad no puede exceder 500 caracteres',
        ];
    }
}

