<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\CambiarEstadoPedidoDTO;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Illuminate\Support\Facades\Log;

final class CambiarEstadoPedidoUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    public function ejecutar(CambiarEstadoPedidoDTO $dto)
    {
        Log::info('[CambiarEstadoPedidoUseCase] Iniciando cambio de estado', [
            'pedido_id' => $dto->pedidoId,
            'nuevo_estado' => $dto->nuevoEstado,
        ]);

        $pedido = $this->pedidoRepository->obtenerPorId($dto->pedidoId);
        
        if (!$pedido) {
            throw new \InvalidArgumentException("Pedido {$dto->pedidoId} no encontrado");
        }

        // Validar transición de estado
        $estadosValidos = [
            'activo' => ['pendiente', 'completado', 'cancelado'],
            'pendiente' => ['activo', 'completado', 'cancelado'],
            'completado' => ['activo', 'pendiente'],
            'cancelado' => [],
        ];

        $estadoActual = $pedido->estado ?? 'activo';
        $estadosSiguientes = $estadosValidos[$estadoActual] ?? [];

        if (!in_array($dto->nuevoEstado, $estadosSiguientes)) {
            throw new \InvalidArgumentException(
                "No se permite cambiar de '{$estadoActual}' a '{$dto->nuevoEstado}'"
            );
        }

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
