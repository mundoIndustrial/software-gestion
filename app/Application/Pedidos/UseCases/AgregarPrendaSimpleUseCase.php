<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaSimpleDTO;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Illuminate\Support\Facades\Auth;

class AgregarPrendaSimpleUseCase
{
    public function __construct(
        private PedidoProduccionRepository $pedidoRepository
    ) {}

    public function ejecutar(AgregarPrendaSimpleDTO $dto): array
    {
        // Obtener el pedido
        $pedido = $this->pedidoRepository->obtenerPorId($dto->pedidoId);

        if (!$pedido) {
            throw new \Exception("Pedido con ID {$dto->pedidoId} no encontrado");
        }

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
