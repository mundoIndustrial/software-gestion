<?php

namespace App\Infrastructure\Services\Operario;

use App\Domain\Operario\Services\PedidoFotosReadService;

class PedidoFotosReadServiceImpl implements PedidoFotosReadService
{
    public function obtenerFotosPedido(int $numeroPedido): array
    {
        $cacheKey = "fotos_pedido_{$numeroPedido}";

        return \Cache::remember($cacheKey, 600, function () use ($numeroPedido) {
            $fotos = [];

            try {
                $pedido = \App\Models\PedidoProduccion::select('id', 'cotizacion_id')
                    ->where('numero_pedido', $numeroPedido)
                    ->first();

                if (!$pedido || !$pedido->cotizacion_id) {
                    return [];
                }

                $prendasCotIds = \App\Models\PrendaCot::where('cotizacion_id', $pedido->cotizacion_id)
                    ->pluck('id')
                    ->toArray();

                if (empty($prendasCotIds)) {
                    return [];
                }

                $fotosPrendas = \App\Models\PrendaFotoCot::select('ruta_webp', 'ruta_original')
                    ->whereIn('prenda_cot_id', $prendasCotIds)
                    ->orderBy('orden')
                    ->get();

                foreach ($fotosPrendas as $foto) {
                    $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                    if ($ruta) {
                        $fotos[] = $ruta;
                    }
                }

                $fotosTelas = \App\Models\PrendaTelaFotoCot::select('ruta_webp', 'ruta_original')
                    ->whereIn('prenda_cot_id', $prendasCotIds)
                    ->orderBy('orden')
                    ->get();

                foreach ($fotosTelas as $foto) {
                    $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                    if ($ruta) {
                        $fotos[] = $ruta;
                    }
                }

                $logoCotIds = \App\Models\LogoCotizacion::select('id')
                    ->where('cotizacion_id', $pedido->cotizacion_id)
                    ->pluck('id')
                    ->toArray();

                if (!empty($logoCotIds)) {
                    $fotosLogos = \App\Models\LogoFotoCot::select('ruta_webp', 'ruta_original')
                        ->whereIn('logo_cotizacion_id', $logoCotIds)
                        ->orderBy('orden')
                        ->get();

                    foreach ($fotosLogos as $foto) {
                        $ruta = $foto->ruta_webp ?: $foto->ruta_original;
                        if ($ruta) {
                            $fotos[] = $ruta;
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error en PedidoFotosReadServiceImpl::obtenerFotosPedido: ' . $e->getMessage());
                return [];
            }

            return $fotos;
        });
    }
}

