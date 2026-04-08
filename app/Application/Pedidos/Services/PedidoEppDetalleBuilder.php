<?php

namespace App\Application\Pedidos\Services;

use Illuminate\Support\Facades\Log;

class PedidoEppDetalleBuilder
{
    public function construirEppsCompletos($modeloEloquent): array
    {
        $epps = [];

        try {
            if (!$modeloEloquent || !$modeloEloquent->epps) {
                Log::debug('[obtenerEppsCompletos] Sin EPPs en modelo', [
                    'modelo_existe' => $modeloEloquent ? true : false,
                    'epps_existe' => $modeloEloquent && $modeloEloquent->epps ? true : false,
                ]);
                return [];
            }

            foreach ($modeloEloquent->epps as $epp) {
                $imagenes = $this->mapearImagenesEpp($epp);

                $epps[] = [
                    'id' => $epp->id,
                    'pedido_epp_id' => $epp->id,
                    'epp_id' => $epp->epp_id,
                    'nombre' => $epp->epp?->nombre_completo ?? $epp->epp?->nombre ?? '',
                    'nombre_completo' => $epp->epp?->nombre_completo ?? $epp->epp?->nombre ?? '',
                    'epp_nombre' => $epp->epp?->nombre_completo ?? $epp->epp?->nombre ?? null,
                    'cantidad' => $epp->cantidad,
                    'observaciones' => $epp->observaciones,
                    'imagenes' => $imagenes,
                ];
            }

            Log::info('EPPs procesados exitosamente', [
                'pedido_id' => $modeloEloquent->id,
                'cantidad' => count($epps),
            ]);
        } catch (\Exception $e) {
            Log::warning('Error obteniendo EPPs', [
                'pedido_id' => $modeloEloquent?->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }

        return $epps;
    }

    private function mapearImagenesEpp($epp): array
    {
        $imagenes = [];

        if ($epp->imagenes && $epp->imagenes->count() > 0) {
            foreach ($epp->imagenes as $imagen) {
                $rutaWebp = $imagen->ruta_webp ?? $imagen->ruta_web ?? $imagen->url ?? null;
                $rutaOriginal = $imagen->ruta_original ?? $rutaWebp ?? null;

                $imagenes[] = [
                    'id' => $imagen->id ?? null,
                    'ruta_webp' => $this->normalizarRutaImagen($rutaWebp),
                    'ruta_original' => $this->normalizarRutaImagen($rutaOriginal),
                    'orden' => (int) ($imagen->orden ?? 0),
                ];
            }

            usort($imagenes, function ($a, $b) {
                return $a['orden'] <=> $b['orden'];
            });
        }

        return $imagenes;
    }

    private function normalizarRutaImagen(?string $ruta): ?string
    {
        $rutaNormalizada = null;

        if (!$ruta) {
            return $rutaNormalizada;
        }

        $ruta = str_replace('\\', '/', $ruta);

        if (str_starts_with($ruta, 'http')) {
            $rutaNormalizada = $ruta;
        } elseif (str_starts_with($ruta, '/storage/')) {
            $rutaNormalizada = $ruta;
        } elseif (str_starts_with($ruta, 'storage/')) {
            $rutaNormalizada = '/' . $ruta;
        } else {
            $rutaNormalizada = '/storage/' . ltrim($ruta, '/');
        }

        return $rutaNormalizada;
    }
}
