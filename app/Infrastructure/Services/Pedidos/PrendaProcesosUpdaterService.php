<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PrendaPedido;
use App\Models\TipoProceso;

final class PrendaProcesosUpdaterService
{
    /**
     * @param array<int, mixed>|null $procesos
     */
    public function actualizarProcesos(PrendaPedido $prenda, ?array $procesos): void
    {
        if (is_null($procesos) || empty($procesos)) {
            return;
        }

        foreach ($procesos as $proceso) {
            if (!is_array($proceso)) {
                continue;
            }

            $ubicaciones = $proceso['ubicaciones'] ?? null;
            if (is_string($ubicaciones)) {
                $decodificadas = json_decode($ubicaciones, true);
                $ubicaciones = is_array($decodificadas) ? $decodificadas : null;
            }

            $procesoId = $proceso['id'] ?? null;
            $procesoExistente = null;

            if ($procesoId) {
                $procesoExistente = $prenda->procesos()->where('id', $procesoId)->first();
            }

            if ($procesoExistente) {
                $procesoExistente->update([
                    'tipo_proceso_id' => $proceso['tipo_proceso_id'] ?? $procesoExistente->tipo_proceso_id,
                    'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : $procesoExistente->ubicaciones,
                    'observaciones' => $proceso['observaciones'] ?? $procesoExistente->observaciones,
                    'estado' => $proceso['estado'] ?? $procesoExistente->estado,
                    'modo_tallas' => $this->resolverModoTallasProceso($proceso, $procesoExistente->modo_tallas ?? 'generico'),
                    'datos_adicionales' => json_encode($proceso),
                ]);

                if (isset($proceso['tallas']) && is_array($proceso['tallas']) && !empty($proceso['tallas'])) {
                    $this->actualizarTallasDelProceso(
                        $procesoExistente,
                        $proceso['tallas'],
                        $proceso['datosExtendidos'] ?? []
                    );
                }

                continue;
            }

            $tipoProcesoId = $proceso['tipo_proceso_id'] ?? null;
            if (!$tipoProcesoId) {
                $tipoProcesoNombre = $proceso['tipo'] ?? $proceso['nombre'] ?? null;
                if ($tipoProcesoNombre) {
                    $tipo = TipoProceso::whereRaw('LOWER(slug) = ?', [strtolower((string) $tipoProcesoNombre)])
                        ->orWhereRaw('LOWER(nombre) = ?', [strtolower((string) $tipoProcesoNombre)])
                        ->first();
                    $tipoProcesoId = $tipo?->id;
                }
            }

            if (!$tipoProcesoId) {
                continue;
            }

            $nuevoProceso = $prenda->procesos()->create([
                'tipo_proceso_id' => $tipoProcesoId,
                'ubicaciones' => !empty($ubicaciones) ? json_encode($ubicaciones) : null,
                'observaciones' => $proceso['observaciones'] ?? null,
                'estado' => $proceso['estado'] ?? 'PENDIENTE',
                'modo_tallas' => $this->resolverModoTallasProceso($proceso, 'generico'),
                'datos_adicionales' => json_encode($proceso),
            ]);

            if (isset($proceso['tallas']) && is_array($proceso['tallas']) && !empty($proceso['tallas'])) {
                $this->actualizarTallasDelProceso(
                    $nuevoProceso,
                    $proceso['tallas'],
                    $proceso['datosExtendidos'] ?? []
                );
            }
        }
    }

    private function actualizarTallasDelProceso($procesoExistente, array $tallasNuevas, array $datosExtendidos = []): void
    {
        try {
            $procesoExistente->tallas()->delete();

            foreach ($tallasNuevas as $genero => $tallas) {
                if (!is_array($tallas)) {
                    continue;
                }

                foreach ($tallas as $tallaKey => $cantidad) {
                    if ($cantidad <= 0) {
                        continue;
                    }

                    $partes = explode('__', (string) $tallaKey);
                    $tallaReal = $partes[0];
                    $colorNombre = $partes[1] ?? null;

                    $ubicacionesTalla = null;
                    $observacionesTalla = null;

                    if (!empty($datosExtendidos)) {
                        $generoLower = strtolower($genero);
                        $tallaDatos = $datosExtendidos[$generoLower][$tallaKey] ?? null;
                        if ($tallaDatos) {
                            if (isset($tallaDatos['ubicaciones']) && !empty($tallaDatos['ubicaciones'])) {
                                $ubicacionesTalla = json_encode($tallaDatos['ubicaciones']);
                            }
                            if (isset($tallaDatos['observaciones'])) {
                                $observacionesTalla = $tallaDatos['observaciones'];
                            }
                        }
                    }

                    $tallaCreada = $procesoExistente->tallas()->create([
                        'genero' => strtoupper($genero),
                        'talla' => strtoupper($tallaReal),
                        'cantidad' => (int) $cantidad,
                        'ubicaciones' => $ubicacionesTalla,
                        'observaciones' => $observacionesTalla,
                    ]);

                    if (!empty($colorNombre)) {
                        $detallesColor = $datosExtendidos[strtolower($genero)][$tallaKey] ?? null;
                        $ubicacionesColor = !empty($detallesColor['ubicaciones']) ? json_encode($detallesColor['ubicaciones']) : $ubicacionesTalla;
                        $observacionesColor = is_array($detallesColor) && array_key_exists('observaciones', $detallesColor)
                            ? $detallesColor['observaciones']
                            : $observacionesTalla;

                        \DB::table('pedidos_procesos_prenda_talla_colores')->insert([
                            'pedidos_procesos_prenda_talla_id' => $tallaCreada->id,
                            'color_nombre' => $colorNombre,
                            'tela_nombre' => null,
                            'cantidad' => (int) $cantidad,
                            'ubicaciones' => $ubicacionesColor,
                            'observaciones' => $observacionesColor,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Log::error('[PrendaProcesosUpdaterService] Error actualizando tallas del proceso', [
                'proceso_id' => $procesoExistente->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolverModoTallasProceso(array $proceso, string $modoFallback = 'generico'): string
    {
        $modo = $proceso['modo_tallas'] ?? $modoFallback;
        $modosValidos = ['general', 'especifico', 'generico'];
        return in_array($modo, $modosValidos, true) ? $modo : $modoFallback;
    }
}

