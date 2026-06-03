<?php

namespace App\Http\Requests\Insumos;

use Illuminate\Foundation\Http\FormRequest;

class GuardarAnchoMetrajeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prenda_pedido_id' => 'nullable|integer|exists:prendas_pedido,id',
            'prenda_bodega_id' => 'nullable|integer|exists:prenda_bodega,id',
            'numero_recibo' => 'nullable|integer|min:1',
            'consecutivo_recibo_id' => 'nullable|integer|exists:consecutivos_recibos_pedidos,id',
            'tipo_recibo' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:100',
            'tela' => 'nullable|string|max:100',
            'talla' => 'nullable|string|max:50',
            'tipo_modo' => 'nullable|in:normal,color,pieza,mano',
            'ancho' => 'nullable|string|max:255',
            'metraje' => 'nullable|string|max:255',
            'contenido_mano' => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'prenda_pedido_id.exists' => 'La prenda de pedido seleccionada no existe',
            'prenda_bodega_id.exists' => 'La prenda de bodega seleccionada no existe',
            'ancho.string' => 'El ancho debe ser texto',
            'metraje.string' => 'El metraje debe ser texto',
            'tipo_modo.in' => 'El tipo de modo es invalido',
        ];
    }
}
