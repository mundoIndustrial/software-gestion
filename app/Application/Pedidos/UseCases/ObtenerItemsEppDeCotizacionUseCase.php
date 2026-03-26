<?php

namespace App\Application\Pedidos\UseCases;

use App\Domain\Epp\Repositories\EppRepository;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerItemsEppDeCotizacionUseCase
 * 
 *  RESPONSABILIDAD ÚNICA: Obtener items EPP asociados a una cotización
 */
class ObtenerItemsEppDeCotizacionUseCase
{
    public function __construct(
        private EppRepository $eppRepository,
    ) {}

    /**
     * Ejecutar obtención
     * 
     * @param int $cotizacionId
     * @return object (con propiedad: items)
     */
    public function ejecutar(int $cotizacionId): object
    {
        Log::info('[ObtenerItemsEppDeCotizacionUseCase] Iniciado', [
            'cotizacion_id' => $cotizacionId,
        ]);

        try {
            // Obtener items EPP de la cotización
            $items = $this->eppRepository->obtenerPorCotizacion($cotizacionId);

            // Formatear para respuesta
            $itemsFormateados = $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'nombre' => $item->nombre,
                    'descripcion' => $item->descripcion,
                    'cantidad' => $item->cantidad ?? 1,
                    'precio' => $item->precio ?? 0,
                ];
            })->toArray();

            Log::info('[ObtenerItemsEppDeCotizacionUseCase] Completado', [
                'cotizacion_id' => $cotizacionId,
                'items' => count($itemsFormateados),
            ]);

            return (object) ['items' => $itemsFormateados];

        } catch (\Exception $e) {
            Log::error('[ObtenerItemsEppDeCotizacionUseCase] Error', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
