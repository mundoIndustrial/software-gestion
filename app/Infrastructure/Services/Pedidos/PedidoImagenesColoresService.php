<?php

namespace App\Infrastructure\Services\Pedidos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidoImagenesColoresService
{
    public function procesarImagenesDeColores($request, int $pedidoId, array $prendas): void
    {
        $fotosColorFiles = $request->file('fotos_color') ?? [];
        $fotosColorMetaAll = $request->input('fotos_color_meta') ?? [];

        if (empty($fotosColorFiles)) {
            return;
        }

        $fotosColorMeta = $this->procesarArchivosDeColores($fotosColorFiles, $fotosColorMetaAll, (int) $pedidoId);
        if (empty($fotosColorMeta)) {
            return;
        }

        $this->inyectarRutasDeColoresEnAsignaciones($pedidoId, $prendas, $fotosColorMeta);
    }

    private function procesarArchivosDeColores(array $fotosColorFiles, array $fotosColorMetaAll, int $pedidoId): array
    {
        $colorFotoServiceCrear = new \App\Infrastructure\Services\Pedidos\TelaFotoService();
        $fotosColorMeta = [];

        foreach ($fotosColorFiles as $indice => $archivo) {
            if (!$archivo || !$archivo->isValid()) {
                continue;
            }

            try {
                $rutas = $colorFotoServiceCrear->procesarFoto($archivo, $pedidoId, true);
                $meta = $this->normalizarMetaColor($fotosColorMetaAll[$indice] ?? null);

                $fotosColorMeta[] = [
                    'ruta_webp' => $rutas['ruta_webp'] ?? $rutas['ruta_original'],
                    'clave' => $meta['clave'] ?? '',
                    'color_nombre' => $meta['color_nombre'] ?? '',
                ];
            } catch (\Exception $e) {
                Log::warning('[PedidoImagenesService] Error procesando imagen de color', [
                    'pedido_id' => $pedidoId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $fotosColorMeta;
    }

    private function normalizarMetaColor(mixed $metaRaw): array
    {
        if (is_string($metaRaw)) {
            $decoded = json_decode($metaRaw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($metaRaw) ? $metaRaw : [];
    }

    private function inyectarRutasDeColoresEnAsignaciones(int $pedidoId, array $prendas, array $fotosColorMeta): void
    {
        foreach ($prendas as $prenda) {
            $asignacionesColores = $prenda['asignacionesColoresPorTalla'] ?? [];
            if (empty($asignacionesColores)) {
                continue;
            }

            foreach ($fotosColorMeta as $fotoMeta) {
                $this->aplicarFotoColorEnAsignaciones($pedidoId, $asignacionesColores, $fotoMeta);
            }
        }
    }

    private function aplicarFotoColorEnAsignaciones(int $pedidoId, array $asignacionesColores, array $fotoMeta): void
    {
        $clave = $fotoMeta['clave'] ?? '';
        $colorNombre = strtoupper($fotoMeta['color_nombre'] ?? '');

        if ($clave === '' || $colorNombre === '') {
            return;
        }

        if (!isset($asignacionesColores[$clave]) || empty($asignacionesColores[$clave]['colores'])) {
            return;
        }

        foreach ($asignacionesColores[$clave]['colores'] as $colorItem) {
            if (strtoupper($colorItem['nombre'] ?? '') !== $colorNombre) {
                continue;
            }

            DB::table('prenda_pedido_talla_colores as pptc')
                ->join('prenda_pedido_tallas as ppt', 'pptc.prenda_pedido_talla_id', '=', 'ppt.id')
                ->join('prendas_pedido as pp', 'ppt.prenda_pedido_id', '=', 'pp.id')
                ->where('pptc.color_nombre', $colorNombre)
                ->where('pp.pedido_produccion_id', $pedidoId)
                ->update(['pptc.imagen_ruta' => $fotoMeta['ruta_webp']]);

            break;
        }
    }
}
