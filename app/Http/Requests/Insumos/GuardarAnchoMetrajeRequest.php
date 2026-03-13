<?php

namespace App\Http\Requests\Insumos;

use Illuminate\Foundation\Http\FormRequest;

class GuardarAnchoMetrajeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization will be handled by controller middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'prenda_pedido_id' => 'required|integer|exists:prendas_pedido,id',
            'color' => 'nullable|string|max:100',
            'tela' => 'nullable|string|max:100',
            'talla' => 'nullable|string|max:50',
            'tipo_modo' => 'nullable|in:normal,color,pieza,mano',
            'ancho' => 'nullable|numeric|min:0',
            'metraje' => 'nullable|numeric|min:0',
            'contenido_mano' => 'nullable|string|max:5000'
        ];
    }

    /**
     * Get custom error messages for validator rules.
     */
    public function messages(): array
    {
        return [
            'prenda_id.required' => 'La prenda es requerida',
            'prenda_id.exists' => 'La prenda seleccionada no existe',
            'ancho.numeric' => 'El ancho debe ser un número',
            'metraje.numeric' => 'El metraje debe ser un número',
            'tipo_modo.in' => 'El tipo de modo es inválido',
        ];
    }
}
