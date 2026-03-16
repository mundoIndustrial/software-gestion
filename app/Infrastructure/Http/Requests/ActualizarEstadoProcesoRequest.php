<?php

namespace App\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request: ActualizarEstadoProcesoRequest
 *
 * Valida los datos del endpoint PATCH /proceso-seguimiento/{id}/estado.
 */
class ActualizarEstadoProcesoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'estado'       => 'required|in:Pendiente,En Progreso,Completado,Pausado',
            'observaciones' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'El estado es requerido.',
            'estado.in'       => 'Estado inválido. Debe ser: Pendiente, En Progreso, Completado o Pausado.',
        ];
    }
}
