<?php

namespace App\Http\Requests\LogoCotizacion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * AgregarTecnicaRequest - Valida datos para agregar una técnica a una cotización
 */
class AgregarTecnicaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo_cotizacion_id' => 'required|integer|min:1',
            'tipo_logo_cotizacion_id' => 'required|integer|in:1,2,3,4',
            'observaciones_tecnica' => 'nullable|string|max:1000',
            'instrucciones_especiales' => 'nullable|string|max:1000',
            'prendas' => 'required|array|min:1',
            'prendas.*.nombre_prenda' => 'required|string|max:100',
            'prendas.*.descripcion' => 'required|string|max:500',
            'prendas.*.ubicaciones' => 'required|array|min:1',
            'prendas.*.ubicaciones.*' => 'required|string|max:100',
            'prendas.*.tallas' => 'nullable|array',
            'prendas.*.tallas.*' => 'nullable|string|max:10',
            'prendas.*.cantidad' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'logo_cotizacion_id.required' => 'La cotización es requerida',
            'tipo_logo_cotizacion_id.required' => 'El tipo de técnica es requerido',
            'tipo_logo_cotizacion_id.in' => 'Tipo de técnica inválido',
            'prendas.required' => 'Debe agregar al menos una prenda',
            'prendas.*.nombre_prenda.required' => 'El nombre de la prenda es requerido',
            'prendas.*.descripcion.required' => 'La descripción de la prenda es requerida',
            'prendas.*.ubicaciones.required' => 'Debe especificar al menos una ubicación',
        ];
    }
}
