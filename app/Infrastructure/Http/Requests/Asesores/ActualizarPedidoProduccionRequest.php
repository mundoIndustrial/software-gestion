<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class ActualizarPedidoProduccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente' => 'sometimes|string|max:255',
            'forma_pago' => 'sometimes|string|in:contado,credito,transferencia,cheque',
        ];
    }
}

