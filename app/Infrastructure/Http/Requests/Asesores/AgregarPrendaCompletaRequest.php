<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class AgregarPrendaCompletaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_prenda' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'origen' => 'required|string|in:bodega,confeccion',
            'cantidad_talla' => 'nullable|json',
            'asignaciones_colores' => 'nullable|json',
            'procesos' => 'nullable|json',
            'variantes' => 'nullable|json',
            'novedad' => 'required|string|max:500',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'nullable|image|max:5120',
            'imagenes_existentes' => 'nullable|json',
            'telas' => 'nullable|json',
        ];
    }
}

