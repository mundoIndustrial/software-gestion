<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class ActualizarPedidoAsesorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'novedades' => 'nullable|string',
            'forma_de_pago' => 'nullable|string|max:69',
            'estado' => 'nullable|string|in:Pendiente,Entregado,En Ejecucion,No iniciado,Anulada,PENDIENTE_SUPERVISOR',
            'area' => 'nullable|string|max:255',
            'prendas' => 'sometimes|array',
            'prendas.*.id' => 'nullable|exists:prendas_pedido,id',
            'prendas.*.nombre_prenda' => 'required_with:prendas|string',
            'prendas.*.talla' => 'nullable|string',
            'prendas.*.cantidad' => 'required_with:prendas|integer|min:1',
            'prendas.*.precio_unitario' => 'nullable|numeric|min:0',
            'epp' => 'sometimes|array',
            'epp.*.id' => 'required_with:epp|integer|exists:pedido_epp,id',
            'epp.*.cantidad' => 'required_with:epp|integer|min:0',
            'epp.*.observaciones' => 'nullable|string',
        ];
    }
}

