<?php

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request: ActualizarProcesoSeguimientoRequest
 *
 * Valida los datos del endpoint PUT /proceso-seguimiento/{id}.
 */
class ActualizarProcesoSeguimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'area'        => 'required|string|max:100',
            'estado'      => 'required|in:Pendiente,En Progreso,Completado,Pausado',
            'fecha_inicio' => 'nullable|date',
            'encargado'   => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'area.required'  => 'El área es requerida.',
            'estado.in'      => 'Estado inválido. Debe ser: Pendiente, En Progreso, Completado o Pausado.',
        ];
    }
}
