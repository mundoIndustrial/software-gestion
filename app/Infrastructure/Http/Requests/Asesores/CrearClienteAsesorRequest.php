<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class CrearClienteAsesorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|unique:clientes,nombre',
            'email' => 'nullable|email',
            'telefono' => 'nullable|string',
            'ciudad' => 'nullable|string',
            'notas' => 'nullable|string',
        ];
    }
}

