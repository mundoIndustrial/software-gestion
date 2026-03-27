<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class ActualizarPrendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pedidoId' => 'required|numeric',
            'prendasIndex' => 'required|numeric|min:0',
            'nombre' => 'sometimes|nullable|string',
            'descripcion' => 'sometimes|nullable|string',
            'talla_referencia' => 'sometimes|nullable|string',
            'tallas' => 'sometimes|nullable|array',
            'infoTecnica' => 'sometimes|nullable|array',
            'observaciones' => 'sometimes|nullable|string',
        ];
    }
}

