<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\ColorPrenda;
use App\Models\PrendaPedido;
use App\Models\TelaPrenda;

final class PrendaAsignacionesColoresUpdaterService
{
    /**
     * @param mixed $asignacionesColores
     * @param array<int, array<string, mixed>>|null $fotosColorProcesadas
     */
    public function actualizarAsignacionesColores(PrendaPedido $prenda, $asignacionesColores, ?array $fotosColorProcesadas = null): void
    {
        if (is_null($asignacionesColores)) {
            \Log::info('[PrendaAsignacionesColoresUpdaterService] asignaciones_colores es NULL - NO se modifican asignaciones', [
                'prenda_id' => $prenda->id,
            ]);
            return;
        }

        $tallasMap = $prenda->tallas()->get()->keyBy(function ($t) {
            return strtoupper($t->genero) . '_' . $t->talla;
        });

        $estaVacio = empty($asignacionesColores)
            || (is_array($asignacionesColores) && count($asignacionesColores) === 0)
            || (is_object($asignacionesColores) && count((array) $asignacionesColores) === 0);

        if ($estaVacio) {
            // Guard rail defensivo:
            // Evita borrar todas las asignaciones por payload vacio accidental
            // durante guardados parciales o desincronizacion en borrador.
            \Log::warning('[PrendaAsignacionesColoresUpdaterService] asignaciones_colores vacio - se omite borrado defensivamente', [
                'prenda_id' => $prenda->id,
                'tipo_vacio' => is_array($asignacionesColores) ? 'array' : (is_object($asignacionesColores) ? 'object' : 'otro'),
                'total_tallas_prenda' => $tallasMap->count(),
            ]);
            return;
        }

        // CACHE DE ASIGNACIONES EXISTENTES: para preservar imagenes y datos al actualizar
        $asignacionesExistentes = \DB::table('prenda_pedido_talla_colores')
            ->whereIn('prenda_pedido_talla_id', $tallasMap->pluck('id')->toArray())
            ->get()
            ->keyBy(function ($c) {
                return strtoupper($c->color_nombre ?? '') . '|' . strtoupper($c->tela_nombre ?? '');
            });

        $asignacionesPlanas = [];
        $fotosColorIndex = [];

        if (!empty($fotosColorProcesadas)) {
            foreach ($fotosColorProcesadas as $fotoColor) {
                $fKey = ($fotoColor['clave'] ?? '') . '|' . strtoupper($fotoColor['color_nombre'] ?? '');
                $fotosColorIndex[$fKey] = $fotoColor['ruta_webp'] ?? null;
            }
        }

        foreach ($asignacionesColores as $key => $asignacion) {
            if (!is_array($asignacion)) {
                continue;
            }

            if (isset($asignacion['colores']) && is_array($asignacion['colores'])) {
                foreach ($asignacion['colores'] as $colorData) {
                    $fKey = $key . '|' . strtoupper($colorData['nombre'] ?? '');
                    $asignacionesPlanas[] = [
                        'genero' => strtoupper($asignacion['genero'] ?? ''),
                        'talla' => $asignacion['talla'] ?? '',
                        'tela_nombre' => $asignacion['tela'] ?? '',
                        'tela_id' => $asignacion['tela_id'] ?? null,
                        'color_nombre' => $colorData['nombre'] ?? '',
                        'color_id' => $colorData['color_id'] ?? null,
                        'cantidad' => (int) ($colorData['cantidad'] ?? 0),
                        'referencia' => $colorData['referencia'] ?? null,
                        'observaciones' => $colorData['observaciones'] ?? null,
                        'imagen_ruta' => $this->normalizarRutaStorage($fotosColorIndex[$fKey] ?? ($colorData['imagen_ruta'] ?? null)),
                    ];
                }
                continue;
            }

            // FLUJO ALTERNATIVO: asignación sin array colores (patrón simple)
            // PRESERVAR imagen_ruta existente si no viene en el payload
            $colorNombre = $asignacion['color'] ?? $asignacion['color_nombre'] ?? '';
            $telaNombre = $asignacion['tela'] ?? $asignacion['tela_nombre'] ?? '';
            $existenteCacheKey = strtoupper($colorNombre) . '|' . strtoupper($telaNombre);
            $imagenExistente = $asignacionesExistentes[$existenteCacheKey]?->imagen_ruta ?? null;

            $asignacionesPlanas[] = [
                'genero' => strtoupper($asignacion['genero'] ?? ''),
                'talla' => $asignacion['talla'] ?? '',
                'tela_nombre' => $telaNombre,
                'tela_id' => $asignacion['tela_id'] ?? null,
                'color_nombre' => $colorNombre,
                'color_id' => $asignacion['color_id'] ?? null,
                'cantidad' => (int) ($asignacion['cantidad'] ?? 0),
                'referencia' => $asignacion['referencia'] ?? null,
                'observaciones' => $asignacion['observaciones'] ?? null,
                'imagen_ruta' => $this->normalizarRutaStorage($asignacion['imagen_ruta'] ?? $imagenExistente),
            ];
        }

        $generosAfectados = array_unique(array_column($asignacionesPlanas, 'genero'));
        foreach ($tallasMap as $talla) {
            if (in_array(strtoupper($talla->genero), $generosAfectados, true)) {
                \DB::table('prenda_pedido_talla_colores')
                    ->where('prenda_pedido_talla_id', $talla->id)
                    ->delete();
            }
        }

        $insertados = 0;
        foreach ($asignacionesPlanas as $asig) {
            if (($asig['cantidad'] ?? 0) <= 0) {
                continue;
            }

            $tallaKey = ($asig['genero'] ?? '') . '_' . ($asig['talla'] ?? '');
            $tallaRecord = $tallasMap[$tallaKey] ?? null;
            if (!$tallaRecord) {
                continue;
            }

            $telaId = $asig['tela_id'] ?? null;
            if (!$telaId && !empty($asig['tela_nombre'])) {
                $tela = TelaPrenda::whereRaw('LOWER(nombre) = ?', [strtolower($asig['tela_nombre'])])->first();
                $telaId = $tela?->id;
            }

            $colorId = $asig['color_id'] ?? null;
            if (!$colorId && !empty($asig['color_nombre'])) {
                $color = ColorPrenda::whereRaw('LOWER(nombre) = ?', [strtolower($asig['color_nombre'])])->first();
                $colorId = $color?->id;
            }

            \DB::table('prenda_pedido_talla_colores')->insert([
                'prenda_pedido_talla_id' => $tallaRecord->id,
                'tela_id' => $telaId ?? 0,
                'tela_nombre' => $asig['tela_nombre'],
                'color_id' => $colorId ?? 0,
                'color_nombre' => $asig['color_nombre'],
                'cantidad' => $asig['cantidad'],
                'referencia' => $asig['referencia'] ?? null,
                'observaciones' => $asig['observaciones'] ?? null,
                'imagen_ruta' => $asig['imagen_ruta'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::debug('[PrendaAsignacionesColoresUpdaterService] INSERT - Asignación guardada', [
                'prenda_id' => $prenda->id,
                'talla_key' => $tallaKey,
                'color' => $asig['color_nombre'],
                'tela' => $asig['tela_nombre'],
                'cantidad' => $asig['cantidad'],
                'imagen_ruta_guardada' => $asig['imagen_ruta'] ?? null,
            ]);

            $insertados++;
        }

        \Log::info('[PrendaAsignacionesColoresUpdaterService] Asignaciones colores actualizadas', [
            'prenda_id' => $prenda->id,
            'insertados' => $insertados,
            'total_recibidos' => count($asignacionesPlanas),
        ]);
    }

    private function normalizarRutaStorage(mixed $ruta): ?string
    {
        if (!is_string($ruta)) {
            return null;
        }

        $ruta = trim(str_replace('\\', '/', $ruta));
        if ($ruta === '') {
            return null;
        }

        if (preg_match('#^https?://[^/]+/storage/(.+)$#i', $ruta, $matches)) {
            return $matches[1];
        }

        if (str_starts_with($ruta, '/storage/')) {
            return substr($ruta, 9);
        }

        if (str_starts_with($ruta, 'storage/')) {
            return substr($ruta, 8);
        }

        return ltrim($ruta, '/');
    }
}

