<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class CrearInventarioTelaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'categoria' => 'required|string|max:100',
            'nombre_tela' => 'required|string|max:100',
            'stock' => 'required|numeric|min:0',
            'metraje_sugerido' => 'nullable|numeric|min:0',
        ];
    }
}

