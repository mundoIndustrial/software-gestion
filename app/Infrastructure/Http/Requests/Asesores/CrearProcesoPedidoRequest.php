<?php

namespace App\Infrastructure\Http\Requests\Asesores;

use Illuminate\Foundation\Http\FormRequest;

final class CrearProcesoPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'numero_pedido' => 'required|integer|min:1',
            'proceso' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'encargado' => 'nullable|string|max:255',
            'estado_proceso' => 'required|in:Pendiente,En Progreso,Completado,Pausado',
        ];
    }
}

