<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class AgregarPrendaSimpleAsesorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_prenda' => 'required|string|max:255',
            'cantidad' => 'required|integer|min:1',
            'descripcion' => 'nullable|string|max:1000',
        ];
    }
}

