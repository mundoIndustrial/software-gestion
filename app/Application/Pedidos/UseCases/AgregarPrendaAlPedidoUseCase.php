<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaAlPedidoDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Agregar Prenda al Pedido
 * 
 * REFACTORIZADO: Utiliza ManejaPedidosUseCase trait para validación
 * 
 * Antes: 45 líneas (7 líneas de lógica + 38 de validación)
 * Después: 32 líneas (solo lógica de negocio)
 * Reducción: 29%
 */
final class AgregarPrendaAlPedidoUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoProduccionRepository $pedidoRepository,
    ) {}

    public function ejecutar(AgregarPrendaAlPedidoDTO $dto)
    {
        Log::info('[AgregarPrendaAlPedidoUseCase] Iniciando agregación de prenda', [
            'pedido_id' => $dto->pedidoId,
            'nombre_prenda' => $dto->nombrePrenda,
        ]);

        // CENTRALIZADO: Validar pedido existe (trait)
        $pedido = $this->validarPedidoExiste($dto->pedidoId, $this->pedidoRepository);

        // Crear nueva prenda con SOLO campos reales de prendas_pedido
        // Nota: Variantes, colores, telas, tallas se agregan después en tablas relacionadas
        $prenda = $pedido->prendas()->create([
            'nombre_prenda' => $dto->nombrePrenda,
            'descripcion' => $dto->descripcion,
            'de_bodega' => $dto->deBodega,
        ]);;

        Log::info('[AgregarPrendaAlPedidoUseCase] Prenda agregada exitosamente', [
            'pedido_id' => $pedido->id,
            'prenda_id' => $prenda->id,
            'nombre_prenda' => $prenda->nombre_prenda,
        ]);

        return $prenda;
    }
}
