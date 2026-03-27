<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class ResumenObservacionesDespachoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pedido_ids' => 'required|array',
            'pedido_ids.*' => 'integer',
        ];
    }
}

