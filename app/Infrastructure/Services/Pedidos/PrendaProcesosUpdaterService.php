<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PrendaPedido;
use App\Models\TipoProceso;
use Illuminate\Support\Facades\Log;

final class PrendaProcesosUpdaterService
{
    /**
     * @param array<int, mixed>|null $procesos
     */
    public function actualizarProcesos(
        PrendaPedido $prenda,
        ?array $procesos,
        array $fotosProcesoNuevo = [],
        array $fotosProcesoTallasNuevo = []
    ): void
    {
        if (is_null($procesos) || empty($procesos)) {
            return;
        }

        foreach ($procesos as $procesoIdx => $proceso) {
            if (!is_array($proceso)) {
                continue;
            }

            $ubicaciones = $proceso['ubicaciones'] ?? null;
            if (is_string($ubicaciones)) {
                $decodificadas = json_decode($ubicaciones, true);
                $ubicaciones = is_array($decodificadas) ? $decodificadas : null;
            }
            if (!is_array($ubicaciones)) {
                $ubicaciones = [];
            }

            $procesoId = $proceso['id'] ?? null;
            $procesoExistente = null;

            if ($procesoId) {
                $procesoExistente = $prenda->procesos()
                    ->withTrashed()
                    ->where('id', $procesoId)
                    ->first();
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

            if (!$procesoExistente && $tipoProcesoId) {
                $procesoExistente = $prenda->procesos()
                    ->withTrashed()
                    ->where('tipo_proceso_id', $tipoProcesoId)
                    ->first();
            }

            if ($procesoExistente) {
                if (method_exists($procesoExistente, 'trashed') && $procesoExistente->trashed()) {
                    $procesoExistente->restore();
                }

                $procesoExistente->update([
                    'tipo_proceso_id' => $tipoProcesoId ?? $procesoExistente->tipo_proceso_id,
                    'ubicaciones' => json_encode($ubicaciones),
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

                $this->guardarImagenesNuevasDelProceso($procesoExistente, $fotosProcesoNuevo[$procesoIdx] ?? []);
                $this->guardarImagenesNuevasPorTallaDelProceso($procesoExistente, $fotosProcesoTallasNuevo, (int) $procesoIdx);

                continue;
            }

            if (!$tipoProcesoId) {
                continue;
            }

            $nuevoProceso = $prenda->procesos()->create([
                'tipo_proceso_id' => $tipoProcesoId,
                'ubicaciones' => json_encode($ubicaciones),
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

            $this->guardarImagenesNuevasDelProceso($nuevoProceso, $fotosProcesoNuevo[$procesoIdx] ?? []);
            $this->guardarImagenesNuevasPorTallaDelProceso($nuevoProceso, $fotosProcesoTallasNuevo, (int) $procesoIdx);
        }
    }

    private function guardarImagenesNuevasDelProceso($proceso, array $fotosNuevas): void
    {
        if (empty($fotosNuevas)) {
            return;
        }

        $ordenInicial = (int) $proceso->imagenes()->max('orden');
        foreach ($fotosNuevas as $idx => $rutasFoto) {
            if (!is_array($rutasFoto)) {
                continue;
            }

            $rutaOriginal = $rutasFoto['ruta_original'] ?? null;
            $rutaWebp = $rutasFoto['ruta_webp'] ?? $rutaOriginal;
            if (!$rutaOriginal && !$rutaWebp) {
                continue;
            }

            $proceso->imagenes()->create([
                'ruta_original' => $rutaOriginal ?? $rutaWebp,
                'ruta_webp' => $rutaWebp ?? $rutaOriginal,
                'orden' => $ordenInicial + $idx + 1,
            ]);
        }
    }

    private function guardarImagenesNuevasPorTallaDelProceso($proceso, array $fotosProcesoTallasNuevo, int $procesoIdx): void
    {
        if (empty($fotosProcesoTallasNuevo)) {
            return;
        }

        foreach ($fotosProcesoTallasNuevo as $key => $imagenes) {
            if (!is_array($imagenes) || empty($imagenes)) {
                continue;
            }

            foreach ($imagenes as $imgData) {
                if (!is_array($imgData)) {
                    continue;
                }

                $imgProcesoIdx = (int) ($imgData['proceso_idx'] ?? -1);
                if ($imgProcesoIdx !== $procesoIdx) {
                    continue;
                }

                $genero = strtoupper((string) ($imgData['genero'] ?? ''));
                $tallaRaw = strtoupper((string) ($imgData['talla'] ?? ''));
                $talla = explode('__', $tallaRaw, 2)[0] ?? $tallaRaw;
                if ($genero === '' || $talla === '') {
                    continue;
                }

                $tallaProceso = $proceso->tallas()
                    ->where('genero', $genero)
                    ->where('talla', $talla)
                    ->orderBy('id')
                    ->first();

                if (!$tallaProceso) {
                    Log::warning('[PrendaProcesosUpdaterService] No se encontro talla de proceso para imagen por talla', [
                        'proceso_detalle_id' => $proceso->id,
                        'proceso_idx' => $procesoIdx,
                        'key' => $key,
                        'genero' => $genero,
                        'talla' => $talla,
                    ]);
                    continue;
                }

                $rutaOriginal = $imgData['ruta_original'] ?? null;
                $rutaWebp = $imgData['ruta_webp'] ?? $rutaOriginal;
                if (!$rutaOriginal && !$rutaWebp) {
                    continue;
                }

                $maxOrden = (int) $tallaProceso->imagenes()->max('orden');
                $orden = $maxOrden + 1;
                $imagenesExistentes = (int) $tallaProceso->imagenes()->count();

                $proceso->imagenes()->create([
                    'proceso_prenda_talla_id' => $tallaProceso->id,
                    'ruta_original' => $rutaOriginal ?? $rutaWebp,
                    'ruta_webp' => $rutaWebp ?? $rutaOriginal,
                    'orden' => $orden,
                    'es_principal' => $imagenesExistentes === 0 ? 1 : 0,
                ]);

                Log::info('[PrendaProcesosUpdaterService] Imagen por talla guardada en proceso', [
                    'proceso_detalle_id' => $proceso->id,
                    'proceso_idx' => $procesoIdx,
                    'proceso_talla_id' => $tallaProceso->id,
                    'genero' => $genero,
                    'talla' => $talla,
                    'orden' => $orden,
                ]);
            }
        }
    }

    private function actualizarTallasDelProceso($procesoExistente, array $tallasNuevas, array $datosExtendidos = []): void
    {
        // Limpieza física previa para evitar conflictos por índice único
        // (proceso_prenda_detalle_id + genero + talla)
        $procesoExistente->load('tallas.coloresAsignados');
        foreach ($procesoExistente->tallas as $talla) {
            $talla->coloresAsignados()->delete();
            $talla->delete();
        }

        $tallasConsolidadas = [];

        foreach ($tallasNuevas as $genero => $tallas) {
            if (!is_array($tallas)) {
                continue;
            }

            $generoUpper = strtoupper((string) $genero);
            $generoLower = strtolower((string) $genero);

            foreach ($tallas as $tallaKey => $cantidad) {
                $cantidadInt = (int) $cantidad;
                if ($cantidadInt <= 0) {
                    continue;
                }

                $partes = explode('__', (string) $tallaKey, 2);
                $tallaReal = strtoupper((string) ($partes[0] ?? ''));
                $colorNombre = isset($partes[1]) ? trim((string) $partes[1]) : null;
                if ($tallaReal === '') {
                    continue;
                }

                $claveBase = $generoUpper . '__' . $tallaReal;
                if (!isset($tallasConsolidadas[$claveBase])) {
                    $tallasConsolidadas[$claveBase] = [
                        'genero' => $generoUpper,
                        'talla' => $tallaReal,
                        'cantidad' => 0,
                        'ubicaciones' => null,
                        'observaciones' => null,
                        'colores' => [],
                    ];
                }

                $tallasConsolidadas[$claveBase]['cantidad'] += $cantidadInt;

                $tallaDatos = $datosExtendidos[$generoLower][$tallaKey] ?? null;
                if (is_array($tallaDatos)) {
                    if (isset($tallaDatos['ubicaciones']) && is_array($tallaDatos['ubicaciones']) && !empty($tallaDatos['ubicaciones'])) {
                        $tallasConsolidadas[$claveBase]['ubicaciones'] = json_encode($tallaDatos['ubicaciones']);
                    }
                    if (array_key_exists('observaciones', $tallaDatos)) {
                        $tallasConsolidadas[$claveBase]['observaciones'] = $tallaDatos['observaciones'];
                    }
                }

                if ($colorNombre !== null && $colorNombre !== '') {
                    $ubicacionesColor = null;
                    $observacionesColor = null;
                    if (is_array($tallaDatos)) {
                        if (isset($tallaDatos['ubicaciones']) && is_array($tallaDatos['ubicaciones']) && !empty($tallaDatos['ubicaciones'])) {
                            $ubicacionesColor = json_encode($tallaDatos['ubicaciones']);
                        }
                        if (array_key_exists('observaciones', $tallaDatos)) {
                            $observacionesColor = $tallaDatos['observaciones'];
                        }
                    }

                    $tallasConsolidadas[$claveBase]['colores'][] = [
                        'color_nombre' => $colorNombre,
                        'cantidad' => $cantidadInt,
                        'ubicaciones' => $ubicacionesColor,
                        'observaciones' => $observacionesColor,
                    ];
                }
            }
        }

        foreach ($tallasConsolidadas as $tallaData) {
            $tallaCreada = $procesoExistente->tallas()->create([
                'genero' => $tallaData['genero'],
                'talla' => $tallaData['talla'],
                'cantidad' => (int) $tallaData['cantidad'],
                'ubicaciones' => $tallaData['ubicaciones'],
                'observaciones' => $tallaData['observaciones'],
            ]);

            foreach ($tallaData['colores'] as $colorData) {
                \DB::table('pedidos_procesos_prenda_talla_colores')->insert([
                    'pedidos_procesos_prenda_talla_id' => $tallaCreada->id,
                    'color_nombre' => $colorData['color_nombre'],
                    'tela_nombre' => null,
                    'cantidad' => (int) $colorData['cantidad'],
                    'ubicaciones' => $colorData['ubicaciones'],
                    'observaciones' => $colorData['observaciones'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function resolverModoTallasProceso(array $proceso, string $modoFallback = 'generico'): string
    {
        $modo = $proceso['modo_tallas'] ?? $proceso['modoTallas'] ?? $modoFallback;
        $modosValidos = ['general', 'especifico', 'generico'];
        return in_array($modo, $modosValidos, true) ? $modo : $modoFallback;
    }
}
