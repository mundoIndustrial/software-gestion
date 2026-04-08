<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class AgregarPrendaSimpleRequest extends FormRequest
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
            'tipo' => 'required|string|in:sin_cotizacion,reflectivo',
            'tipo_manga' => 'required|string|max:100',
            'tipo_broche' => 'required|string|max:100',
            'color_id' => 'required|integer|min:1',
            'tela_id' => 'required|integer|min:1',
        ];
    }
}

