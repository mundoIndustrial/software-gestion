<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaSimpleDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Illuminate\Support\Facades\Auth;

/**
 * Use Case: Agregar Prenda Simple
 * 
 * REFACTORIZADO: FASE 3 - Validaciones centralizadas
 * 
 * Antes: 44 líneas | Después: ~28 líneas | Reducción: ~36%
 */
class AgregarPrendaSimpleUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoProduccionRepository $pedidoRepository
    ) {}

    public function ejecutar(AgregarPrendaSimpleDTO $dto): array
    {
        // CENTRALIZADO: Validar pedido existe (trait)
        $pedido = $this->validarPedidoExiste($dto->pedidoId, $this->pedidoRepository);

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
