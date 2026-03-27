<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ActualizarClienteAsesorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clienteId = (int) $this->route('id');

        return [
            'nombre' => [
                'required',
                'string',
                Rule::unique('clientes', 'nombre')->ignore($clienteId),
            ],
            'email' => 'nullable|email',
            'telefono' => 'nullable|string',
            'ciudad' => 'nullable|string',
            'notas' => 'nullable|string',
        ];
    }
}

