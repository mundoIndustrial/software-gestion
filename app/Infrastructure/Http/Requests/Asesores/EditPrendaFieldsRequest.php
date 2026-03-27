<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class EditPrendaFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_prenda' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'cantidad' => 'nullable|integer|min:0',
            'de_bodega' => 'nullable|boolean',
        ];
    }
}

