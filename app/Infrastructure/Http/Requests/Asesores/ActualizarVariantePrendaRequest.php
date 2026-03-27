<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class ActualizarVariantePrendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_manga_id' => 'sometimes|nullable|integer|min:1',
            'manga_obs' => 'sometimes|nullable|string|max:500',
            'tipo_broche_boton_id' => 'sometimes|nullable|integer|min:1',
            'broche_boton_obs' => 'sometimes|nullable|string|max:500',
            'tiene_bolsillos' => 'sometimes|nullable|boolean',
            'bolsillos_obs' => 'sometimes|nullable|string|max:500',
        ];
    }
}

