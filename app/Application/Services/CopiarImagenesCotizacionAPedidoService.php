<?php

namespace App\Application\Services;

use App\Models\PrendaCot;
use App\Models\PrendaPed;
use App\Models\PrendaFotoPed;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para copiar imágenes de cotización a pedido
 * 
 * Responsabilidades:
 * - Copiar fotos de prendas de cotización a pedido
 * - Copiar fotos de telas de cotización a pedido
 * - Mantener orden y referencias correctas
 */
class CopiarImagenesCotizacionAPedidoService
{
    /**
     * Copiar todas las imágenes de una cotización a sus prendas de pedido
     * 
     * Estrategia: Copiar imágenes de TODAS las prendas de cotización a TODAS las prendas de pedido
     * Sin depender del orden, ya que ambas se crean en el mismo orden
     */
    public function copiarImagenesCotizacionAPedido(int $cotizacionId, int $pedidoId): void
    {
        try {
            // Obtener todas las prendas de la cotización con sus fotos
            $prendasCotizacion = PrendaCot::where('cotizacion_id', $cotizacionId)
                ->with(['fotos', 'telaFotos'])
                ->orderBy('id')
                ->get();

            if ($prendasCotizacion->isEmpty()) {
                Log::info('No hay prendas en la cotización para copiar imágenes', [
                    'cotizacion_id' => $cotizacionId,
                    'pedido_id' => $pedidoId
                ]);
                return;
            }

            // Obtener prendas del pedido en el mismo orden de creación
            $prendasPedido = PrendaPed::where('pedido_produccion_id', $pedidoId)
                ->orderBy('id')
                ->get();

            if ($prendasPedido->isEmpty()) {
                Log::warning('No hay prendas en el pedido para copiar imágenes', [
                    'cotizacion_id' => $cotizacionId,
                    'pedido_id' => $pedidoId
                ]);
                return;
            }

            // Validar que tenemos la misma cantidad de prendas
            if ($prendasCotizacion->count() !== $prendasPedido->count()) {
                Log::warning('Cantidad de prendas no coincide', [
                    'cotizacion_id' => $cotizacionId,
                    'pedido_id' => $pedidoId,
                    'prendas_cot' => $prendasCotizacion->count(),
                    'prendas_ped' => $prendasPedido->count()
                ]);
            }

            // Copiar imágenes para cada prenda (por índice)
            $totalImagenesCopiadas = 0;
            foreach ($prendasCotizacion as $index => $prendaCot) {
                $prendaPed = $prendasPedido->get($index);
                
                if (!$prendaPed) {
                    Log::warning('Prenda de pedido no encontrada en índice', [
                        'cotizacion_id' => $cotizacionId,
                        'prenda_cot_id' => $prendaCot->id,
                        'index' => $index
                    ]);
                    continue;
                }

                // Copiar fotos de prenda
                $fotosCopiadas = $this->copiarFotosPrenda($prendaCot, $prendaPed);
                $totalImagenesCopiadas += $fotosCopiadas;

                // Copiar fotos de tela
                $fotosTelaCopiadas = $this->copiarFotosTela($prendaCot, $prendaPed);
                $totalImagenesCopiadas += $fotosTelaCopiadas;
            }

            Log::info('✅ Imágenes copiadas exitosamente de cotización a pedido', [
                'cotizacion_id' => $cotizacionId,
                'pedido_id' => $pedidoId,
                'prendas_procesadas' => $prendasCotizacion->count(),
                'total_imagenes_copiadas' => $totalImagenesCopiadas
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al copiar imágenes de cotización a pedido', [
                'cotizacion_id' => $cotizacionId,
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Copiar fotos de prenda de cotización a pedido
     * 
     * @return int Cantidad de fotos copiadas
     */
    private function copiarFotosPrenda(PrendaCot $prendaCot, PrendaPed $prendaPed): int
    {
        try {
            $fotos = $prendaCot->fotos()->orderBy('orden')->get();

            if ($fotos->isEmpty()) {
                Log::debug('Prenda sin fotos', [
                    'prenda_cot_id' => $prendaCot->id,
                    'prenda_ped_id' => $prendaPed->id
                ]);
                return 0;
            }

            foreach ($fotos as $foto) {
                PrendaFotoPed::create([
                    'prenda_ped_id' => $prendaPed->id,
                    'ruta_original' => $foto->ruta_original,
                    'ruta_webp' => $foto->ruta_webp,
                    'ruta_miniatura' => $foto->ruta_miniatura,
                    'orden' => $foto->orden,
                    'ancho' => $foto->ancho,
                    'alto' => $foto->alto,
                    'tamaño' => $foto->tamaño,
                ]);
            }

            Log::info('📸 Fotos de prenda copiadas', [
                'prenda_cot_id' => $prendaCot->id,
                'prenda_ped_id' => $prendaPed->id,
                'cantidad_fotos' => $fotos->count()
            ]);

            return $fotos->count();

        } catch (\Exception $e) {
            Log::error('❌ Error al copiar fotos de prenda', [
                'prenda_cot_id' => $prendaCot->id,
                'prenda_ped_id' => $prendaPed->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Copiar fotos de tela de cotización a pedido
     * 
     * @return int Cantidad de fotos de tela copiadas
     */
    private function copiarFotosTela(PrendaCot $prendaCot, PrendaPed $prendaPed): int
    {
        try {
            $fotosTela = $prendaCot->telaFotos()->orderBy('orden')->get();

            if ($fotosTela->isEmpty()) {
                Log::debug('Prenda sin fotos de tela', [
                    'prenda_cot_id' => $prendaCot->id,
                    'prenda_ped_id' => $prendaPed->id
                ]);
                return 0;
            }

            // Primero crear una tela en el pedido para asociar las fotos
            $telaPed = \App\Models\PrendaTelaPed::create([
                'prenda_ped_id' => $prendaPed->id,
                'color_id' => null,
                'tela_id' => null,
            ]);

            // Luego copiar las fotos de tela
            foreach ($fotosTela as $foto) {
                \App\Models\PrendaTalaFotoPed::create([
                    'prenda_tela_ped_id' => $telaPed->id,
                    'ruta_original' => $foto->ruta_original,
                    'ruta_webp' => $foto->ruta_webp,
                    'ruta_miniatura' => $foto->ruta_miniatura,
                    'orden' => $foto->orden,
                    'ancho' => $foto->ancho,
                    'alto' => $foto->alto,
                    'tamaño' => $foto->tamaño,
                ]);
            }

            Log::info('🧵 Fotos de tela copiadas', [
                'prenda_cot_id' => $prendaCot->id,
                'prenda_ped_id' => $prendaPed->id,
                'tela_ped_id' => $telaPed->id,
                'cantidad_fotos_tela' => $fotosTela->count()
            ]);

            return $fotosTela->count();

        } catch (\Exception $e) {
            Log::error('❌ Error al copiar fotos de tela', [
                'prenda_cot_id' => $prendaCot->id,
                'prenda_ped_id' => $prendaPed->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
