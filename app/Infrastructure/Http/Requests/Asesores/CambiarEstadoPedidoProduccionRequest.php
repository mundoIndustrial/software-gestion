<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class CambiarEstadoPedidoProduccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nuevo_estado' => 'required|string|in:activo,pendiente,completado,cancelado',
            'razon' => 'sometimes|string|max:500',
        ];
    }
}

