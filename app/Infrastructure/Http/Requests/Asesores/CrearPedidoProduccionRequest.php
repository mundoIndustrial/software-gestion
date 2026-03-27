<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class CrearPedidoProduccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_pedido' => 'required|string|max:50',
            'cliente' => 'required|string|max:255',
            'forma_pago' => 'required|string|in:contado,credito,transferencia,cheque',
            'asesor_id' => 'required|integer|min:1',
            'cantidad_inicial' => 'sometimes|integer|min:0',
            'epps' => 'sometimes|array',
            'epps.*.epp_id' => 'required_with:epps|integer|min:1',
            'epps.*.cantidad' => 'sometimes|integer|min:1',
            'epps.*.observaciones' => 'sometimes|nullable|string|max:1000',
        ];
    }
}

