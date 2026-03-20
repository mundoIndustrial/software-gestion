<?php

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request: GuardarProcesoSeguimientoRequest
 *
 * Centraliza la validación del endpoint POST /proceso-seguimiento/guardar.
 * El controller queda libre de lógica de validación (SRP).
 */
class GuardarProcesoSeguimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autenticación se gestiona en el middleware de la ruta.
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'pedido_produccion_id' => 'required|integer|exists:pedidos_produccion,numero_pedido',
            'prenda_id'            => 'required|integer|exists:prendas_pedido,id',
            'area'                 => 'required|string|max:255',
            'estado'               => 'required|in:Pendiente,En Progreso,Completado,Pausado',
            'observaciones'        => 'nullable|string|max:1000',
        ];

        // El encargado solo es obligatorio para áreas que lo requieren
        $area = $this->input('area');
        if ($area) {
            $areaLower = strtolower($area);
            $needsEncargado = ['corte', 'costura', 'control de calidad'];
            $areaRequiresEncargado = collect($needsEncargado)->contains(fn($reqArea) => str_contains($areaLower, $reqArea));
            
            if ($areaRequiresEncargado) {
                $rules['encargado'] = 'required|string|max:100';
            } else {
                $rules['encargado'] = 'nullable|string|max:100';
            }
        } else {
            $rules['encargado'] = 'required|string|max:100'; // Default si no hay área
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'pedido_produccion_id.required' => 'El pedido es requerido.',
            'pedido_produccion_id.exists'   => 'El pedido indicado no existe.',
            'prenda_id.required'            => 'La prenda es requerida.',
            'prenda_id.exists'              => 'La prenda indicada no existe.',
            'area.required'                 => 'El área es requerida.',
            'estado.in'                     => 'Estado inválido. Debe ser: Pendiente, En Progreso, Completado o Pausado.',
        ];

        // Mensaje de encargado solo si el área lo requiere
        $area = $this->input('area');
        if ($area) {
            $areaLower = strtolower($area);
            $needsEncargado = ['corte', 'costura', 'control de calidad'];
            $areaRequiresEncargado = collect($needsEncargado)->contains(fn($reqArea) => str_contains($areaLower, $reqArea));
            
            if ($areaRequiresEncargado) {
                $messages['encargado.required'] = 'El encargado es requerido para esta área.';
            }
        }

        return $messages;
    }
}
