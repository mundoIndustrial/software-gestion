<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class ActualizarProcesoEspecificoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_proceso_id' => 'nullable|integer',
            'ubicaciones' => 'nullable',
            'imagenes' => 'nullable',
            'imagenes_existentes' => 'nullable',
            'observaciones' => 'nullable|string|max:1000',
            'tallas' => 'nullable',
            'imagenes_nuevas' => 'nullable|array',
            'imagenes_nuevas.*' => 'file|image|max:5120',
        ];
    }
}

