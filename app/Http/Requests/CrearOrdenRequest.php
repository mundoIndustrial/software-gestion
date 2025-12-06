<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request: CrearOrdenRequest
 * 
 * Valida los datos antes de que lleguen al Application Service.
 */
class CrearOrdenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero' => 'required|integer|min:1|unique:tabla_original,pedido',
            'cliente' => 'required|string|max:255',
            'forma_pago' => 'required|string|in:Contado,Crédito 15 días,Crédito 30 días,Crédito 60 días,Transferencia,Cheque',
            'area' => 'required|string|in:Corte,Producción,Polo,Costura,Acabado',
            'prendas' => 'array|min:1',
            'prendas.*.nombre_prenda' => 'required|string|max:255',
            'prendas.*.cantidad_total' => 'required|integer|min:1',
            'prendas.*.cantidad_talla' => 'array',
            'prendas.*.cantidad_talla.*' => 'integer|min:0',
            'prendas.*.descripcion' => 'nullable|string',
            'prendas.*.color_id' => 'nullable|integer|exists:colores_prenda,id',
            'prendas.*.tela_id' => 'nullable|integer|exists:telas_prenda,id',
        ];
    }

    public function messages(): array
    {
        return [
            'numero.required' => 'El número de orden es requerido',
            'numero.unique' => 'El número de orden ya existe',
            'cliente.required' => 'El cliente es requerido',
            'forma_pago.in' => 'Forma de pago inválida',
            'area.in' => 'Área inválida',
            'prendas.min' => 'Debe incluir al menos una prenda',
        ];
    }
}
