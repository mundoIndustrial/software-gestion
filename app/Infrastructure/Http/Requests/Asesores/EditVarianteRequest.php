<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class EditVarianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_manga_id' => 'nullable|integer|min:1',
            'tipo_broche_boton_id' => 'nullable|integer|min:1',
            'tiene_bolsillos' => 'nullable|boolean',
            'obs_bolsillos' => 'nullable|string|max:1000',
            'tiene_reflectivo' => 'nullable|boolean',
            'obs_reflectivo' => 'nullable|string|max:1000',
            'colores' => 'nullable|array',
            'telas' => 'nullable|array',
        ];
    }
}

