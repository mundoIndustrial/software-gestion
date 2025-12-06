<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request: ActualizarOrdenRequest
 * 
 * Valida datos para actualización de órdenes.
 */
class ActualizarOrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => 'required|string|in:Borrador,Aprobada,EnProduccion,Completada,Cancelada',
            'cliente' => 'nullable|string|max:255',
            'forma_pago' => 'nullable|string|in:Contado,Crédito 15 días,Crédito 30 días,Crédito 60 días,Transferencia,Cheque',
            'area' => 'nullable|string|in:Corte,Producción,Polo,Costura,Acabado',
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'El estado es requerido',
            'estado.in' => 'Estado inválido',
        ];
    }
}
