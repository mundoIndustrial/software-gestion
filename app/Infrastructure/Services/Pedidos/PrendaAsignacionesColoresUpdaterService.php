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
            return;
        }

        $tallasMap = $prenda->tallas()->get()->keyBy(function ($t) {
            return strtoupper($t->genero) . '_' . $t->talla;
        });

        $estaVacio = empty($asignacionesColores)
            || (is_array($asignacionesColores) && count($asignacionesColores) === 0)
            || (is_object($asignacionesColores) && count((array) $asignacionesColores) === 0);

        if ($estaVacio) {
            foreach ($tallasMap as $talla) {
                \DB::table('prenda_pedido_talla_colores')
                    ->where('prenda_pedido_talla_id', $talla->id)
                    ->delete();
            }
            \Log::info('[PrendaAsignacionesColoresUpdaterService] Asignaciones colores eliminadas (vacío)', [
                'prenda_id' => $prenda->id,
            ]);
            return;
        }

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
                        'imagen_ruta' => $fotosColorIndex[$fKey] ?? null,
                    ];
                }
                continue;
            }

            $asignacionesPlanas[] = [
                'genero' => strtoupper($asignacion['genero'] ?? ''),
                'talla' => $asignacion['talla'] ?? '',
                'tela_nombre' => $asignacion['tela'] ?? $asignacion['tela_nombre'] ?? '',
                'tela_id' => $asignacion['tela_id'] ?? null,
                'color_nombre' => $asignacion['color'] ?? $asignacion['color_nombre'] ?? '',
                'color_id' => $asignacion['color_id'] ?? null,
                'cantidad' => (int) ($asignacion['cantidad'] ?? 0),
                'referencia' => $asignacion['referencia'] ?? null,
                'observaciones' => $asignacion['observaciones'] ?? null,
                'imagen_ruta' => null,
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

            $insertados++;
        }

        \Log::info('[PrendaAsignacionesColoresUpdaterService] Asignaciones colores actualizadas', [
            'prenda_id' => $prenda->id,
            'insertados' => $insertados,
            'total_recibidos' => count($asignacionesPlanas),
        ]);
    }
}

