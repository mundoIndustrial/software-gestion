<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class AjustarStockInventarioTelaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tela_id' => 'required|integer|exists:inventario_telas,id',
            'tipo_accion' => 'required|in:entrada,salida',
            'cantidad' => 'required|numeric|min:0.01',
            'observaciones' => 'nullable|string',
        ];
    }
}

