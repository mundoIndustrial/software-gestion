<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaAlPedidoDTO;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Illuminate\Support\Facades\Log;

final class AgregarPrendaAlPedidoUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    public function ejecutar(AgregarPrendaAlPedidoDTO $dto)
    {
        Log::info('[AgregarPrendaAlPedidoUseCase] Iniciando agregación de prenda', [
            'pedido_id' => $dto->pedidoId,
            'nombre_prenda' => $dto->nombrePrenda,
        ]);

        $pedido = $this->pedidoRepository->obtenerPorId($dto->pedidoId);
        
        if (!$pedido) {
            throw new \InvalidArgumentException("Pedido {$dto->pedidoId} no encontrado");
        }

        // Crear nueva prenda con SOLO campos reales de prendas_pedido
        // Nota: Variantes, colores, telas, tallas se agregan después en tablas relacionadas
        $prenda = $pedido->prendas()->create([
            'nombre_prenda' => $dto->nombrePrenda,
            'descripcion' => $dto->descripcion,
            'de_bodega' => $dto->deBodega,
        ]);

        Log::info('[AgregarPrendaAlPedidoUseCase] Prenda agregada exitosamente', [
            'pedido_id' => $pedido->id,
            'prenda_id' => $prenda->id,
            'nombre_prenda' => $prenda->nombre_prenda,
        ]);

        return $prenda;
    }
}
