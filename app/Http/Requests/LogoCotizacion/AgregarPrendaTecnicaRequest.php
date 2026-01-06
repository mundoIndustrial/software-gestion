<?php

namespace App\Http\Requests\LogoCotizacion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * AgregarPrendaTecnicaRequest - Valida datos para agregar una prenda a una técnica
 */
class AgregarPrendaTecnicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo_cotizacion_tecnica_id' => 'required|integer|min:1',
            'nombre_prenda' => 'required|string|max:100',
            'descripcion' => 'required|string|max:500',
            'ubicaciones' => 'required|array|min:1',
            'ubicaciones.*' => 'required|string|max:100',
            'tallas' => 'nullable|array',
            'tallas.*' => 'nullable|string|max:10',
            'cantidad' => 'nullable|integer|min:1|max:1000',
            'especificaciones' => 'nullable|string|max:1000',
            'color_hilo' => 'nullable|string|max:50',
            'puntos_estimados' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_prenda.required' => 'El nombre de la prenda es requerido',
            'descripcion.required' => 'La descripción es requerida',
            'ubicaciones.required' => 'Debe seleccionar al menos una ubicación',
        ];
    }
}
