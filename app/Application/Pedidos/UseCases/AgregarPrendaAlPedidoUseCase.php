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

        // Crear nueva prenda
        $prenda = $pedido->prendas()->create([
            'nombre_prenda' => $dto->nombrePrenda,
            'cantidad' => $dto->cantidad,
            'tipo_manga' => $dto->tipoManga,
            'tipo_broche' => $dto->tipoBroche,
            'color_id' => $dto->colorId,
            'tela_id' => $dto->telaId,
            'descripcion' => $dto->descripcion,
            'origen' => $dto->origen,
        ]);

        // Guardar tallas si existen
        if (!empty($dto->tallas)) {
            $this->pedidoRepository->guardarTallasDesdeJson($prenda->id, json_encode($dto->tallas));
        }

        Log::info('[AgregarPrendaAlPedidoUseCase] Prenda agregada exitosamente', [
            'pedido_id' => $pedido->id,
            'prenda_id' => $prenda->id,
        ]);

        return $pedido;
    }
}
