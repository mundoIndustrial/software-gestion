<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class HomologarEppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pedido_epp_id' => 'required|numeric|min:1',
            'motivo' => 'required|string|min:5|max:1000',
            'cantidad' => 'required|numeric|min:1',
            'observaciones' => 'nullable|string',
            'epp_id' => 'nullable|numeric|min:1',
        ];
    }
}

