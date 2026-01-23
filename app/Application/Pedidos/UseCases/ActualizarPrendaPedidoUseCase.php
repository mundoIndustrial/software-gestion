<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPrendaPedidoDTO;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

final class ActualizarPrendaPedidoUseCase
{
    public function ejecutar(ActualizarPrendaPedidoDTO $dto)
    {
        Log::info('[ActualizarPrendaPedidoUseCase] Iniciando actualización de prenda', [
            'prenda_id' => $dto->prendaId,
        ]);

        $prenda = PrendaPedido::find($dto->prendaId);
        
        if (!$prenda) {
            throw new \InvalidArgumentException("Prenda {$dto->prendaId} no encontrada");
        }

        // Actualizar SOLO campos reales de prendas_pedido
        if ($dto->nombrePrenda !== null) {
            $prenda->nombre_prenda = $dto->nombrePrenda;
        }
        
        if ($dto->descripcion !== null) {
            $prenda->descripcion = $dto->descripcion;
        }
        
        if ($dto->deBodega !== null) {
            $prenda->de_bodega = $dto->deBodega;
        }

        $prenda->save();

        Log::info('[ActualizarPrendaPedidoUseCase] Prenda actualizada exitosamente', [
            'prenda_id' => $prenda->id,
        ]);

        return $prenda;
    }
}
