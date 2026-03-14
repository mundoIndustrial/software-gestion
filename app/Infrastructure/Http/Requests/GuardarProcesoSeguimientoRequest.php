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
        return [
            'pedido_produccion_id' => 'required|integer|exists:pedidos_produccion,numero_pedido',
            'prenda_id'            => 'required|integer|exists:prendas_pedido,id',
            'area'                 => 'required|string|max:255',
            'estado'               => 'required|in:Pendiente,En Progreso,Completado,Pausado',
            'encargado'            => 'required|string|max:100',
            'observaciones'        => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'pedido_produccion_id.required' => 'El pedido es requerido.',
            'pedido_produccion_id.exists'   => 'El pedido indicado no existe.',
            'prenda_id.required'            => 'La prenda es requerida.',
            'prenda_id.exists'              => 'La prenda indicada no existe.',
            'area.required'                 => 'El área es requerida.',
            'estado.in'                     => 'Estado inválido. Debe ser: Pendiente, En Progreso, Completado o Pausado.',
            'encargado.required'            => 'El encargado es requerido.',
        ];
    }
}
