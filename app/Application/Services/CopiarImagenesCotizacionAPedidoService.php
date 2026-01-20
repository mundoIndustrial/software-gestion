<?php

namespace App\Application\Services;

use App\Models\PrendaCot;
use App\Models\PrendaPedido;
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
     * Copiar imágenes del reflectivo seleccionadas por el usuario
     */
    public function copiarImagenesReflectivo(int $cotizacionId, int $pedidoId, array $fotosIdsSeleccionadas): void
    {
        \Log::info(' [CopiarImagenes] Copiando imágenes de reflectivo', [
            'cotizacion_id' => $cotizacionId,
            'pedido_id' => $pedidoId,
            'fotos_seleccionadas' => $fotosIdsSeleccionadas
        ]);

        try {
            // Obtener el reflectivo de la cotización
            $reflectivo = \App\Models\ReflectivoCotizacion::where('cotizacion_id', $cotizacionId)->first();
            
            if (!$reflectivo) {
                \Log::info('No hay reflectivo en la cotización');
                return;
            }

            \Log::info(' Buscando fotos de reflectivo', [
                'reflectivo_id' => $reflectivo->id,
                'fotos_ids_seleccionadas' => $fotosIdsSeleccionadas
            ]);

            // Obtener las fotos seleccionadas
            $fotosReflectivo = \App\Models\ReflectivoCotizacionFoto::whereIn('id', $fotosIdsSeleccionadas)
                ->where('reflectivo_cotizacion_id', $reflectivo->id)
                ->get();

            \Log::info(' Fotos encontradas', [
                'cantidad' => $fotosReflectivo->count(),
                'fotos' => $fotosReflectivo->toArray()
            ]);

            if ($fotosReflectivo->isEmpty()) {
                \Log::warning(' No hay fotos de reflectivo para copiar', [
                    'reflectivo_id' => $reflectivo->id,
                    'ids_buscados' => $fotosIdsSeleccionadas
                ]);
                return;
            }

            // Obtener el pedido y su primera prenda
            $pedido = \App\Models\PedidoProduccion::findOrFail($pedidoId);
            $primeraPrenda = \App\Models\PrendaPedido::where('numero_pedido', $pedido->numero_pedido)
                ->orderBy('id')
                ->first();

            if (!$primeraPrenda) {
                \Log::warning('No hay prendas en el pedido para copiar imágenes de reflectivo');
                return;
            }

            // Copiar las fotos seleccionadas a la primera prenda
            $fotosCopiadas = 0;
            foreach ($fotosReflectivo as $foto) {
                \Log::info(' Copiando foto de reflectivo', [
                    'foto_id' => $foto->id,
                    'ruta_original' => $foto->ruta_original,
                    'ruta_webp' => $foto->ruta_webp,
                    'prenda_pedido_id' => $primeraPrenda->id
                ]);

                $fotoCreada = \App\Models\PrendaFotoPedido::create([
                    'prenda_pedido_id' => $primeraPrenda->id,
                    'ruta_original' => $foto->ruta_original,
                    'ruta_webp' => $foto->ruta_webp,
                    'ruta_miniatura' => null,
                    'orden' => $foto->orden ?? 0,
                    'ancho' => null,
                    'alto' => null,
                    'tamaño' => null,
                ]);

                \Log::info(' Foto copiada', [
                    'prenda_foto_pedido_id' => $fotoCreada->id
                ]);

                $fotosCopiadas++;
            }

            \Log::info(' Imágenes de reflectivo copiadas exitosamente', [
                'cantidad_fotos' => $fotosCopiadas,
                'prenda_pedido_id' => $primeraPrenda->id
            ]);

        } catch (\Exception $e) {
            \Log::error(' Error al copiar imágenes de reflectivo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Copiar todas las imágenes de una cotización a sus prendas de pedido
     * 
     * Estrategia: Copiar imágenes de TODAS las prendas de cotización a TODAS las prendas de pedido
     * Sin depender del orden, ya que ambas se crean en el mismo orden
     */
    public function copiarImagenesCotizacionAPedido(int $cotizacionId, int $pedidoId): void
    {
        \Log::info(' [CopiarImagenes] Iniciando copia de imágenes', [
            'cotizacion_id' => $cotizacionId,
            'pedido_id' => $pedidoId
        ]);

        try {
            // Obtener todas las prendas de la cotización con sus fotos
            $prendasCotizacion = PrendaCot::where('cotizacion_id', $cotizacionId)
                ->with(['fotos', 'telaFotos'])
                ->orderBy('id')
                ->get();

            \Log::info(' [CopiarImagenes] Prendas de cotización encontradas', [
                'total_prendas_cot' => $prendasCotizacion->count()
            ]);

            // Obtener logos de la cotización
            $logoCotizacion = \App\Models\LogoCotizacion::where('cotizacion_id', $cotizacionId)
                ->with(['fotos'])
                ->first();

            if ($prendasCotizacion->isEmpty()) {
                Log::info('No hay prendas en la cotización para copiar imágenes', [
                    'cotizacion_id' => $cotizacionId,
                    'pedido_id' => $pedidoId
                ]);
                return;
            }

            // Obtener prendas del pedido en el mismo orden de creación
            // Obtener el numero_pedido desde el pedido_produccion_id
            $pedido = \App\Models\PedidoProduccion::findOrFail($pedidoId);
            $prendasPedido = PrendaPedido::where('numero_pedido', $pedido->numero_pedido)
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

                // Copiar logos (una sola vez por cotización, para la primera prenda)
                if ($index === 0 && $logoCotizacion) {
                    $logosCopiados = $this->copiarLogos($logoCotizacion, $prendaPed);
                    $totalImagenesCopiadas += $logosCopiados;
                }
            }

            Log::info(' Imágenes copiadas exitosamente de cotización a pedido', [
                'cotizacion_id' => $cotizacionId,
                'pedido_id' => $pedidoId,
                'prendas_procesadas' => $prendasCotizacion->count(),
                'total_imagenes_copiadas' => $totalImagenesCopiadas
            ]);

        } catch (\Exception $e) {
            Log::error(' Error al copiar imágenes de cotización a pedido', [
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
    private function copiarFotosPrenda(PrendaCot $prendaCot, PrendaPedido $prendaPedido): int
    {
        try {
            $fotos = $prendaCot->fotos()->orderBy('orden')->get();

            if ($fotos->isEmpty()) {
                Log::debug('Prenda sin fotos', [
                    'prenda_cot_id' => $prendaCot->id,
                    'prenda_pedido_id' => $prendaPedido->id
                ]);
                return 0;
            }

            foreach ($fotos as $foto) {
                \App\Models\PrendaFotoPedido::create([
                    'prenda_pedido_id' => $prendaPedido->id,
                    'ruta_original' => $foto->ruta_original,
                    'ruta_webp' => $foto->ruta_webp,
                    'ruta_miniatura' => $foto->ruta_miniatura,
                    'orden' => $foto->orden,
                    'ancho' => $foto->ancho,
                    'alto' => $foto->alto,
                    'tamaño' => $foto->tamaño,
                ]);
            }

            Log::info(' Fotos de prenda copiadas', [
                'prenda_cot_id' => $prendaCot->id,
                'prenda_pedido_id' => $prendaPedido->id,
                'cantidad_fotos' => $fotos->count()
            ]);

            return $fotos->count();

        } catch (\Exception $e) {
            Log::error(' Error al copiar fotos de prenda', [
                'prenda_cot_id' => $prendaCot->id,
                'prenda_pedido_id' => $prendaPedido->id,
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
    private function copiarFotosTela(PrendaCot $prendaCot, PrendaPedido $prendaPedido): int
    {
        try {
            $fotosTela = $prendaCot->telaFotos()->orderBy('orden')->get();

            if ($fotosTela->isEmpty()) {
                Log::debug('Prenda sin fotos de tela', [
                    'prenda_cot_id' => $prendaCot->id,
                    'prenda_pedido_id' => $prendaPedido->id
                ]);
                return 0;
            }

            // Copiar las fotos de tela directamente a prenda_fotos_tela_pedido
            foreach ($fotosTela as $foto) {
                \App\Models\PrendaFotoTelaPedido::create([
                    'prenda_pedido_id' => $prendaPedido->id,
                    'tela_id' => null,
                    'color_id' => null,
                    'ruta_original' => $foto->ruta_original,
                    'ruta_webp' => $foto->ruta_webp,
                    'ruta_miniatura' => $foto->ruta_miniatura,
                    'orden' => $foto->orden,
                    'ancho' => $foto->ancho,
                    'alto' => $foto->alto,
                    'tamaño' => $foto->tamaño,
                ]);
            }

            Log::info(' Fotos de tela copiadas', [
                'prenda_cot_id' => $prendaCot->id,
                'prenda_pedido_id' => $prendaPedido->id,
                'cantidad_fotos_tela' => $fotosTela->count()
            ]);

            return $fotosTela->count();

        } catch (\Exception $e) {
            Log::error(' Error al copiar fotos de tela', [
                'prenda_cot_id' => $prendaCot->id,
                'prenda_pedido_id' => $prendaPedido->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Copiar logos de cotización a pedido
     * 
     * @return int Cantidad de logos copiados
     */
    private function copiarLogos(\App\Models\LogoCotizacion $logoCotizacion, PrendaPedido $prendaPedido): int
    {
        try {
            $fotosLogos = $logoCotizacion->fotos()->orderBy('orden')->get();

            if ($fotosLogos->isEmpty()) {
                Log::debug('Cotización sin fotos de logos', [
                    'logo_cotizacion_id' => $logoCotizacion->id,
                    'prenda_pedido_id' => $prendaPedido->id
                ]);
                return 0;
            }

            // Copiar las fotos de logo a prenda_fotos_logo_pedido
            foreach ($fotosLogos as $foto) {
                \App\Models\PrendaFotoLogoPedido::create([
                    'prenda_pedido_id' => $prendaPedido->id,
                    'ruta_original' => $foto->ruta_original,
                    'ruta_webp' => $foto->ruta_webp,
                    'ruta_miniatura' => $foto->ruta_miniatura,
                    'orden' => $foto->orden,
                    'ancho' => $foto->ancho,
                    'alto' => $foto->alto,
                    'tamaño' => $foto->tamaño,
                ]);
            }

            Log::info(' Fotos de logo copiadas', [
                'logo_cotizacion_id' => $logoCotizacion->id,
                'prenda_pedido_id' => $prendaPedido->id,
                'cantidad_fotos_logo' => $fotosLogos->count()
            ]);

            return $fotosLogos->count();

        } catch (\Exception $e) {
            Log::error(' Error al copiar fotos de logo', [
                'logo_cotizacion_id' => $logoCotizacion->id,
                'prenda_pedido_id' => $prendaPedido->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
