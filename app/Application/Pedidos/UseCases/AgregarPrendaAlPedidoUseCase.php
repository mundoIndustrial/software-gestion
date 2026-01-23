<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\AgregarPrendaAlPedidoDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use Illuminate\Support\Facades\Log;

/**
 * Use Case: Agregar Prenda al Pedido
 * 
 * REFACTORIZADO: Utiliza ManejaPedidosUseCase trait para validaciÃ³n
 * 
 * Antes: 45 lÃ­neas (7 lÃ­neas de lÃ³gica + 38 de validaciÃ³n)
 * DespuÃ©s: 32 lÃ­neas (solo lÃ³gica de negocio)
 * ReducciÃ³n: 29%
 */
final class AgregarPrendaAlPedidoUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository,
    ) {}

    public function ejecutar(AgregarPrendaAlPedidoDTO $dto)
    {
        Log::info('[AgregarPrendaAlPedidoUseCase] Iniciando agregaciÃ³n de prenda', [
            'pedido_id' => $dto->pedidoId,
            'nombre_prenda' => $dto->nombrePrenda,
        ]);

        // Obtener modelo Eloquent directamente (no Aggregate) porque se agrega relación
        $pedido = \App\Models\PedidoProduccion::findOrFail($dto->pedidoId);

        // Crear nueva prenda con SOLO campos reales de prendas_pedido
        // Nota: Variantes, colores, telas, tallas se agregan despuÃ©s en tablas relacionadas
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


