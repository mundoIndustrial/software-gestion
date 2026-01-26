<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\CambiarEstadoPedidoDTO;
use App\Application\Pedidos\Catalogs\EstadoPedidoCatalog;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Cambiar Estado del Pedido
 * 
 * REFACTORIZADO: Utiliza ManejaPedidosUseCase trait + EstadoPedidoCatalog
 * 
 * Antes: 58 lÃ­neas (15 lÃ­neas de lógica + 43 de validación y transiciones hardcodeadas)
 * DespuÃ©s: 28 lÃ­neas (solo lógica de negocio)
 * Reducción: 52%
 * 
 * Beneficios:
 * - Validaciones centralizadas
 * - Transiciones en Ãºnico lugar (EstadoPedidoCatalog)
 * - Mensajes de error consistentes
 * - Menos código repetido
 */
final class CambiarEstadoPedidoUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository,
    ) {}

    public function ejecutar(CambiarEstadoPedidoDTO $dto)
    {
        Log::info('[CambiarEstadoPedidoUseCase] Iniciando cambio de estado', [
            'pedido_id' => $dto->pedidoId,
            'nuevo_estado' => $dto->nuevoEstado,
        ]);

        // Obtener modelo Eloquent directamente (no Aggregate) porque se actualiza la BD
        $pedido = \App\Models\PedidoProduccion::findOrFail($dto->pedidoId);

        // CENTRALIZADO: Validar estado es vÃ¡lido (trait + catalog)
        $this->validarEstadoValido($dto->nuevoEstado);

        // CENTRALIZADO: Validar transición es permitida (trait + catalog)
        $this->validarTransicion($pedido->estado ?? 'PENDIENTE_SUPERVISOR', $dto->nuevoEstado);

        // Actualizar estado
        $pedido->estado = $dto->nuevoEstado;
        if ($dto->razon) {
            $pedido->razon_cambio_estado = $dto->razon;
        }
        $pedido->save();

        Log::info('[CambiarEstadoPedidoUseCase] Estado cambiado exitosamente', [
            'pedido_id' => $pedido->id,
            'nuevo_estado' => $pedido->estado,
        ]);

        return $pedido;
    }
}


