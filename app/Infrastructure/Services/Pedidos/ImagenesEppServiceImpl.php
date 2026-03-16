<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Domain\Pedidos\Contracts\ImagenesEppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ImagenesEppServiceImpl
 * 
 * Implementación de ImagenesEppService
 */
class ImagenesEppServiceImpl implements ImagenesEppService
{
    public function obtenerImagenesEpp(int $pedidoEppId): array
    {
        try {
            $imagenesData = DB::table('pedido_epp_imagenes')
                ->where('pedido_epp_id', $pedidoEppId)
                ->orderBy('orden', 'asc')
                ->get(['ruta_web', 'ruta_original', 'principal', 'orden']);

            if ($imagenesData->isEmpty()) {
                return [];
            }

            $imagenes = [];
            foreach ($imagenesData as $img) {
                $ruta = $img->ruta_web ?? $img->ruta_original;

                if (empty($ruta)) {
                    continue;
                }

                if (!str_starts_with($ruta, '/storage/')) {
                    $ruta = str_starts_with($ruta, 'storage/') ? '/' . $ruta : '/storage/' . $ruta;
                }

                $imagenes[] = [
                    'ruta_webp' => $ruta,
                    'ruta_original' => $ruta,
                    'ruta_web' => $ruta,
                    'principal' => $img->principal ?? false,
                    'orden' => $img->orden ?? 0,
                ];
            }

            return $imagenes;

        } catch (\Exception $e) {
            Log::error('[ImagenesEppService] Error', [
                'pedido_epp_id' => $pedidoEppId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
