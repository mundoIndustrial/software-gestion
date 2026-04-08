<?php

namespace App\Application\Services\Pedidos;

use Illuminate\Support\Collection;
use App\Models\Cotizacion;
use App\Domain\Epp\Repositories\EppRepository;
use App\Application\Services\Pedidos\Contracts\ObtenerItemsServiceInterface;
use App\Domain\Pedidos\Events\ItemsObtuvieronEvent;
use Illuminate\Support\Facades\Event;

/**
 * ObtenerItemsEppCotizacionService
 * 
 * PHASE 13 (Marzo 2026): Refactoring HTTP to Service
 * 
 * Responsabilidad ÚNICA: Obtener items de una cotización con todas las 
 * transformaciones necesarias para renderizar en el frontend
 * 
 * Procesa:
 * 1. Obtener items crudos de BD
 * 2. Obtener/crear EPPs (batch, sin N+1)
 * 3. Mapear a formato UI
 * 4. Dispara: ItemsObtuvieronEvent (evento de dominio)
 * 
 * Controller solo hace autorización + HTTP
 */
class ObtenerItemsEppCotizacionService implements ObtenerItemsServiceInterface
{
    public function __construct(
        private EppRepository $eppRepository,
        private MapearItemsEppCotizacionService $mapearItemsEpp,
    ) {}

    /**
     * Ejecutar obtención de items para una cotización
     * 
     * FLUJO COMPLETO (sin lógica HTTP):
     * 1. Obtener items de BD
     * 2. Obtener imágenes de items
     * 3. Obtener/crear EPPs (batch)
     * 4. Mapear a formato UI
     * 5. DISPARAR evento de dominio
     * 
     * @param Cotizacion $cotizacion
     * @return array ['items' => Collection, 'count_items' => int, 'count_epps' => int]
     */
    public function ejecutar(Cotizacion $cotizacion): array
    {
        // ====== PASO 1: Datos crudos de BD ======
        $items = $this->eppRepository->obtenerItemsCotizacion($cotizacion->id);
        $itemIds = $items->pluck('id')->all();
        $imgs = $this->eppRepository->obtenerImagenesCotizacion($itemIds);

        // ====== PASO 2: Obtener o crear EPPs (BATCH - sin N+1) ======
        $nombres = $items->pluck('nombre')
            ->map(fn($n) => trim((string)($n ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $eppExistentes = $this->mapearItemsEpp->obtenerOCrearEpps($nombres);

        // ====== PASO 3: Mapear a formato UI (via Servicio) ======
        $itemsUi = $this->mapearItemsEpp->mapearItems($items, $imgs, $eppExistentes);

        // ====== PASO 4: DISPARAR evento de dominio ======
        Event::dispatch(new ItemsObtuvieronEvent(
            cotizacionId: $cotizacion->id,
            itemsCount: count($items),
            eppsCount: count($eppExistentes),
        ));

        return [
            'items' => $itemsUi,
            'count_items' => count($items),
            'count_epps' => count($eppExistentes),
        ];
    }
}
