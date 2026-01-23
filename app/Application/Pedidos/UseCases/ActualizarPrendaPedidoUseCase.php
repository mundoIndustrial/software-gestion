<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPrendaPedidoDTO;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

final class ActualizarPrendaPedidoUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    public function ejecutar(ActualizarPrendaPedidoDTO $dto)
    {
        Log::info('[ActualizarPrendaPedidoUseCase] Iniciando actualización de prenda', [
            'pedido_id' => $dto->pedidoId,
            'prenda_index' => $dto->prendaIndex,
        ]);

        $pedido = $this->pedidoRepository->obtenerPorId($dto->pedidoId);
        
        if (!$pedido) {
            throw new \InvalidArgumentException("Pedido {$dto->pedidoId} no encontrado");
        }

        // Obtener la prenda por índice
        $prendas = $pedido->prendas()->get();
        if (!isset($prendas[$dto->prendaIndex])) {
            throw new \InvalidArgumentException("Prenda en índice {$dto->prendaIndex} no encontrada");
        }

        $prenda = $prendas[$dto->prendaIndex];

        // Actualizar campos
        if ($dto->nombre) {
            $prenda->nombre_prenda = $dto->nombre;
        }
        if ($dto->descripcion) {
            $prenda->descripcion = $dto->descripcion;
        }
        if ($dto->tallas) {
            $prenda->cantidad_talla = json_encode($dto->tallas);
        }

        $prenda->save();

        Log::info('[ActualizarPrendaPedidoUseCase] Prenda actualizada exitosamente', [
            'pedido_id' => $pedido->id,
            'prenda_id' => $prenda->id,
        ]);

        return $prenda;
    }
}
