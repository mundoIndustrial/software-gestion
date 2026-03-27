<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class ObtenerProcesosPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'prenda_id' => 'nullable|integer|min:1',
        ];
    }
}

