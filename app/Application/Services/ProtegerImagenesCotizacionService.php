<?php

namespace App\Application\Services;

use App\Models\PrendaCot;
use App\Models\PrendaPedido;
use App\Models\PrendaFotoPedido;
use Illuminate\Support\Facades\Log;
use DB;

/**
 * Servicio para proteger imágenes de cotizaciones al editar pedidos
 * 
 * Responsabilidades:
 * - Clonar imágenes de cotización al pedido cuando se modifica
 * - Evitar modificar las imágenes originales de la cotización
 * - Manejar eliminaciones de imágenes solo en el contexto del pedido
 */
class ProtegerImagenesCotizacionService
{
    /**
     * Procesar imágenes de una prenda de cotización para un pedido
     * 
     * @param int $cotizacionId ID de la cotización
     * @param int $prendaCotizacionId ID de la prenda en la cotización
     * @param int $prendaPedidoId ID de la prenda en el pedido
     * @param array $imagenesEliminadas Índices de imágenes eliminadas del pedido
     * @return void
     */
    public function procesarImagenesPrenda($cotizacionId, $prendaCotizacionId, $prendaPedidoId, $imagenesEliminadas = [])
    {
        Log::info('[ProtegerImagenes] Procesando imágenes de prenda', [
            'cotizacion_id' => $cotizacionId,
            'prenda_cotizacion_id' => $prendaCotizacionId,
            'prenda_pedido_id' => $prendaPedidoId,
            'imagenes_eliminadas_count' => count($imagenesEliminadas)
        ]);

        try {
            // Obtener la prenda de cotización con sus imágenes
            $prendaCot = PrendaCot::where('cotizacion_id', $cotizacionId)
                ->where('id', $prendaCotizacionId)
                ->with(['fotos' => function($query) {
                    $query->orderBy('orden');
                }])
                ->firstOrFail();

            // Obtener imágenes existentes del pedido
            $imagenesExistentes = PrendaFotoPedido::where('prenda_pedido_id', $prendaPedidoId)
                ->orderBy('orden')
                ->get()
                ->keyBy('orden'); // key by orden para fácil comparación

            Log::info('[ProtegerImagenes] Estado actual', [
                'imagenes_cotizacion' => $prendaCot->fotos->count(),
                'imagenes_pedido_existentes' => $imagenesExistentes->count(),
                'indices_eliminados' => $imagenesEliminadas
            ]);

            // Procesar cada imagen de la cotización
            $imagenesFinales = [];
            $ordenActual = 0;

            foreach ($prendaCot->fotos as $index => $fotoCot) {
                // Si esta imagen fue eliminada del pedido, omitirla
                if (in_array($index, $imagenesEliminadas)) {
                    Log::info('[ProtegerImagenes] Imagen omitida (eliminada del pedido)', [
                        'index' => $index,
                        'ruta_original' => $fotoCot->ruta_original
                    ]);
                    continue;
                }

                // Verificar si ya existe una imagen clonada para esta posición
                $imagenExistente = $imagenesExistentes->get($ordenActual);
                
                if ($imagenExistente) {
                    // Ya existe una imagen clonada, mantenerla
                    $imagenesFinales[] = $imagenExistente;
                    Log::debug('[ProtegerImagenes] Manteniendo imagen existente', [
                        'orden' => $ordenActual,
                        'ruta' => $imagenExistente->ruta_original
                    ]);
                } else {
                    // Crear nueva imagen clonada
                    $imagenClonada = $this->clonarImagen($fotoCot, $prendaPedidoId, $ordenActual);
                    $imagenesFinales[] = $imagenClonada;
                    Log::info('[ProtegerImagenes] Imagen clonada creada', [
                        'orden' => $ordenActual,
                        'ruta_original' => $fotoCot->ruta_original,
                        'nuevo_id' => $imagenClonada->id
                    ]);
                }

                $ordenActual++;
            }

            // Eliminar imágenes sobrantes del pedido (si hay más de las necesarias)
            $this->limpiarImagenesSobrantes($prendaPedidoId, count($imagenesFinales));

            Log::info('[ProtegerImagenes] Procesamiento completado', [
                'imagenes_finales' => count($imagenesFinales),
                'prenda_pedido_id' => $prendaPedidoId
            ]);

        } catch (\Exception $e) {
            Log::error('[ProtegerImagenes] Error procesando imágenes', [
                'cotizacion_id' => $cotizacionId,
                'prenda_cotizacion_id' => $prendaCotizacionId,
                'prenda_pedido_id' => $prendaPedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Clonar una imagen de cotización a pedido
     * 
     * @param \App\Models\PrendaFotoCot $fotoOriginal
     * @param int $prendaPedidoId
     * @param int $orden
     * @return \App\Models\PrendaFotoPedido
     */
    private function clonarImagen($fotoOriginal, $prendaPedidoId, $orden)
    {
        return PrendaFotoPedido::create([
            'prenda_pedido_id' => $prendaPedidoId,
            'ruta_original' => $fotoOriginal->ruta_original,
            'ruta_webp' => $fotoOriginal->ruta_webp,
            'ruta_miniatura' => $fotoOriginal->ruta_miniatura,
            'orden' => $orden,
            'ancho' => $fotoOriginal->ancho,
            'alto' => $fotoOriginal->alto,
            'tamaño' => $fotoOriginal->tamaño,
            'foto_cotizacion_id' => $fotoOriginal->id, // Referencia a la original
            'clonada_de_cotizacion' => true
        ]);
    }

    /**
     * Limpiar imágenes sobrantes del pedido
     * 
     * @param int $prendaPedidoId
     * @param int $cantidadMantenida
     * @return void
     */
    private function limpiarImagenesSobrantes($prendaPedidoId, $cantidadMantenida)
    {
        $imagenesSobrantes = PrendaFotoPedido::where('prenda_pedido_id', $prendaPedidoId)
            ->where('orden', '>=', $cantidadMantenida)
            ->get();

        if ($imagenesSobrantes->isNotEmpty()) {
            $idsEliminados = $imagenesSobrantes->pluck('id')->toArray();
            PrendaFotoPedido::whereIn('id', $idsEliminados)->delete();
            
            Log::info('[ProtegerImagenes] Imágenes sobrantes eliminadas', [
                'prenda_pedido_id' => $prendaPedidoId,
                'cantidad_mantenida' => $cantidadMantenida,
                'eliminadas' => count($idsEliminados),
                'ids' => $idsEliminados
            ]);
        }
    }

    /**
     * Verificar si una prenda viene de cotización
     * 
     * @param array $prendaDatos
     * @return bool
     */
    public function esPrendaDeCotizacion($prendaDatos)
    {
        return !empty($prendaDatos['cotizacion_id']) || 
               ($prendaDatos['tipo'] ?? '') === 'cotizacion';
    }

    /**
     * Procesar múltiples prendas con protección de imágenes
     * 
     * @param array $prendasDatos
     * @return void
     */
    public function procesarPrendasConProteccion($prendasDatos)
    {
        foreach ($prendasDatos as $index => $prendaDatos) {
            if (!$this->esPrendaDeCotizacion($prendaDatos)) {
                continue; // No es prenda de cotización, omitir
            }

            $imagenesEliminadas = $prendaDatos['imagenes_eliminadas'] ?? [];
            
            if (!empty($imagenesEliminadas)) {
                $this->procesarImagenesPrenda(
                    $prendaDatos['cotizacion_id'],
                    $prendaDatos['prenda_id'],
                    $prendaDatos['prenda_pedido_id'] ?? null,
                    $imagenesEliminadas
                );
            }
        }
    }
}
