<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class ActualizarPrendaCompletaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prenda_id' => 'required|numeric|min:1',
            'nombre_prenda' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'origen' => 'nullable|string|in:bodega,confeccion',
            'de_bodega' => 'nullable|in:0,1',
            'tallas' => 'nullable|json',
            'variantes' => 'nullable|json',
            'colores_telas' => 'nullable|json',
            'fotos_telas' => 'nullable|json',
            'fotosTelas' => 'nullable|json',
            'procesos' => 'nullable|json',
            'fotos_procesos' => 'nullable|json',
            'novedad' => 'required|string|max:500',
            'asignaciones_colores' => 'nullable|json',
            'imagenes' => 'nullable|array',
            'imagenes.*' => 'nullable|image|max:5120',
            'imagenes_existentes' => 'nullable|json',
            'imagenes_a_eliminar' => 'nullable|json',
            'procesos_a_eliminar' => 'nullable|json',
        ];
    }
}

