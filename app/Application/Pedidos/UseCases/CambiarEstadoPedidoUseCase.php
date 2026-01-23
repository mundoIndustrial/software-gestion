<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\CambiarEstadoPedidoDTO;
use App\Application\Pedidos\Catalogs\EstadoPedidoCatalog;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Cambiar Estado del Pedido
 * 
 * REFACTORIZADO: Utiliza ManejaPedidosUseCase trait + EstadoPedidoCatalog
 * 
 * Antes: 58 líneas (15 líneas de lógica + 43 de validación y transiciones hardcodeadas)
 * Después: 28 líneas (solo lógica de negocio)
 * Reducción: 52%
 * 
 * Beneficios:
 * - Validaciones centralizadas
 * - Transiciones en único lugar (EstadoPedidoCatalog)
 * - Mensajes de error consistentes
 * - Menos código repetido
 */
final class CambiarEstadoPedidoUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    public function ejecutar(CambiarEstadoPedidoDTO $dto)
    {
        Log::info('[CambiarEstadoPedidoUseCase] Iniciando cambio de estado', [
            'pedido_id' => $dto->pedidoId,
            'nuevo_estado' => $dto->nuevoEstado,
        ]);

        // CENTRALIZADO: Validar pedido existe (trait)
        $pedido = $this->validarPedidoExiste($dto->pedidoId, $this->pedidoRepository);

        // CENTRALIZADO: Validar estado es válido (trait + catalog)
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
