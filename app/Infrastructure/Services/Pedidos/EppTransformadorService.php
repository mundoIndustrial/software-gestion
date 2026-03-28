<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Domain\Pedidos\Services\EppTransformadorServiceContract;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * EppTransformadorService
 * 
 * Servicio que encapsula la transformación de EPPs para visualización.
 * 
 * Responsabilidades:
 * - Transformar EPPs con imágenes
 * - Normalizar rutas de imágenes
 */
class EppTransformadorService implements EppTransformadorServiceContract
{
    /**
     * Transforma EPPs enriqueciendo con imágenes
     * 
     * Acepta cualquier iterable: Collection de Eloquent, array, etc.
     * 
     * @param iterable $epps Relación de EPPs del pedido
     * @return array EPPs transformados con imágenes
     */
    public function transformarEpps($epps)
    {
        if (!$epps) {
            return [];
        }

        $eppsList = [];

        foreach ($epps as $pedidoEpp) {
            $epp = $pedidoEpp->epp;

            if (!$epp) {
                Log::debug('[EppTransformadorService] EPP sin relación válida', [
                    'pedido_epp_id' => $pedidoEpp->id
                ]);
                continue;
            }

            $imagenes = $this->obtenerImagenesEpp($pedidoEpp->id);

            $eppsList[] = [
                'id' => $pedidoEpp->id,
                'epp_id' => $pedidoEpp->epp_id,
                'nombre' => $epp->nombre_completo ?? $epp->nombre ?? '',
                'nombre_completo' => $epp->nombre_completo ?? $epp->nombre ?? '',
                'cantidad' => $pedidoEpp->cantidad ?? 0,
                'observaciones' => $pedidoEpp->observaciones ?? '',
                'imagen' => !empty($imagenes) ? $imagenes[0] : null,
                'imagenes' => $imagenes,
            ];
        }

        return $eppsList;
    }

    /**
     * Obtiene imágenes de un EPP con rutas normalizadas
     */
    private function obtenerImagenesEpp(int $pedidoEppId): array
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
            Log::error('[EppTransformadorService] Error obtener imágenes de EPP', [
                'pedido_epp_id' => $pedidoEppId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {EppTransformadorService}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}
