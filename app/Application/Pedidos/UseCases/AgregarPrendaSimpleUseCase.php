<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaSimpleDTO;
use App\Application\Pedidos\Exceptions\AgregarPrendaSimpleException;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use Illuminate\Support\Facades\Auth;

/**
 * Use Case: Agregar Prenda Simple
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * Antes: 44 lineas | despues: ~28 lineas | Reducción: ~36%
 */
class AgregarPrendaSimpleUseCase
{
    use ManejaPedidosUseCase;

    public function ejecutar(AgregarPrendaSimpleDTO $dto): array
    {
        // Obtener modelo Eloquent directamente (no Aggregate) porque accede a asesor_id y relaciones
        $pedido = \App\Models\PedidoProduccion::findOrFail($dto->pedidoId);

        // Validar permisos (solo el asesor que creó puede agregar prendas)
        if ($pedido->asesor_id !== Auth::id()) {
            throw AgregarPrendaSimpleException::sinPermiso();
        }

        // Crear la prenda
        $prenda = $pedido->prendas()->create([
            'nombre_prenda' => $dto->nombrePrenda,
            'cantidad' => $dto->cantidad,
            'descripcion' => $dto->descripcion,
        ]);

        return [
            'success' => true,
            'id' => $prenda->id,
            'nombre_prenda' => $prenda->nombre_prenda,
            'cantidad' => $prenda->cantidad,
            'descripcion' => $prenda->descripcion,
        ];
    }
}
