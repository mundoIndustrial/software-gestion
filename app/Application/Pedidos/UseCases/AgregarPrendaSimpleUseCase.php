<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaSimpleDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use Illuminate\Support\Facades\Auth;

/**
 * Use Case: Agregar Prenda Simple
 * 
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * 
 * Antes: 44 lÃ­neas | DespuÃ©s: ~28 lÃ­neas | Reducción: ~36%
 */
class AgregarPrendaSimpleUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function ejecutar(AgregarPrendaSimpleDTO $dto): array
    {
        // Obtener modelo Eloquent directamente (no Aggregate) porque accede a asesor_id y relaciones
        $pedido = \App\Models\PedidoProduccion::findOrFail($dto->pedidoId);

        // Validar permisos (solo el asesor que creó puede agregar prendas)
        if ($pedido->asesor_id !== Auth::id()) {
            throw new \Exception("No tienes permiso para agregar prendas a este pedido");
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


