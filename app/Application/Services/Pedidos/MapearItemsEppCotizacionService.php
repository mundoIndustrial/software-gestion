<?php

namespace App\Application\Services\Pedidos;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Domain\Epp\Repositories\EppRepository;

/**
 * MapearItemsEppCotizacionService
 * 
 * RESPONSABILIDAD ÚNICA:
 * - Transformar items de cotización en formato UI
 * - Mapear nombres de items a EPPs en catálogo
 * - Procesar imágenes de items
 * 
 * SACADO DEL CONTROLLER (Refactor Fase 9):
 * Antes: obtenerItemsEppCotizacion() en CrearPedidoEditableController
 * Ahora: Servicio especializado en mapeo
 */
class MapearItemsEppCotizacionService
{
    public function __construct(
        private EppRepository $eppRepository
    ) {}

    /**
     * Mapear items de cotización a formato UI
     * 
     * Entradas (BD):
     * - items: [id, nombre, cantidad, observaciones]
     * - imgs: Array[item_id => [ruta1, ruta2, ...]]
     * - eppExistentes: Array[NOMBRE => epp_id]
     * 
     * Salida:
     * - Array con estructura UI lista para renderizar
     * 
     * @param Collection $items
     * @param array $imgs
     * @param array $eppExistentes
     * @return Collection
     */
    public function mapearItems(Collection $items, array $imgs, array $eppExistentes): Collection
    {
        return $items->map(function ($it) use ($imgs, $eppExistentes) {
            // Procesar imágenes
            $rutasItem = $imgs[$it->id] ?? [];
            $imagenes = $this->procesarImagenes($rutasItem);

            // Obtener ID del EPP del catálogo
            $nombre = trim((string) ($it->nombre ?? ''));
            $eppCatalogoId = $this->obtenerEppCatalogoId($nombre, $eppExistentes);

            return [
                'id' => (int) $it->id,
                'tipo' => 'epp',
                'nombre' => $it->nombre,
                'epp_id' => $eppCatalogoId,
                'cantidad' => (int) ($it->cantidad ?? 1),
                'observaciones' => $it->observaciones,
                'imagenes' => $imagenes,
            ];
        })->values();
    }

    /**
     * Procesar rutas de imágenes
     * 
     * @param array $rutas
     * @return array
     */
    private function procesarImagenes(array $rutas): array
    {
        return array_values(array_filter(array_map(function ($ruta) {
            if (!$ruta) return null;
            return url('/storage/' . ltrim($ruta, '/'));
        }, $rutas)));
    }

    /**
     * Obtener ID del EPP del catálogo
     * 
     * @param string $nombre
     * @param array $eppExistentes
     * @return int
     */
    private function obtenerEppCatalogoId(string $nombre, array $eppExistentes): int
    {
        if ($nombre && isset($eppExistentes[strtoupper($nombre)])) {
            return (int) $eppExistentes[$nombre];
        }
        return 0;
    }

    /**
     * Obtener o crear EPPs para nombres dados (BATCH)
     * 
     * Elimina N+1 queries:
     * - Obtiene todos los EPPs de una vez
     * - Identifica faltantes
     * - Inserta faltantes en batch
     * 
     * @param array $nombres
     * @return array [NOMBRE => epp_id]
     */
    public function obtenerOCrearEpps(array $nombres): array
    {
        if (empty($nombres)) {
            return [];
        }

        // Step 1: Obtener EPPs existentes
        $eppExistentes = $this->eppRepository->obtenerEppsPorNombres($nombres);

        // Step 2: Identificar faltantes
        $nombresFaltantes = array_filter(
            $nombres,
            fn($n) => !isset($eppExistentes[strtoupper($n)])
        );

        // Step 3: Insertar faltantes en batch (NO N+1)
        if (!empty($nombresFaltantes)) {
            $datosInsercion = $this->eppRepository->construirDatosInsercion($nombresFaltantes);
            $this->eppRepository->insertarEppsBatch($datosInsercion);

            // Step 4: Re-obtener recientes y mergear
            $eppRecientes = $this->eppRepository->obtenerEppsRecienInsertados($nombresFaltantes);
            $eppExistentes = array_merge($eppExistentes, $eppRecientes);
        }

        Log::info('[MapearItemsEppCotizacionService] EPPs procesados (BATCH)', [
            'totales' => count($nombres),
            'existentes' => count($eppExistentes),
            'insertados' => count($nombresFaltantes ?? []),
        ]);

        return $eppExistentes;
    }
}
