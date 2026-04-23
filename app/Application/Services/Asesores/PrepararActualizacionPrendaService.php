<?php

namespace App\Application\Services\Asesores;

use App\Application\Pedidos\UseCases\EliminarProcesosListaUseCase;
use App\Application\Services\Pedidos\ProcesarImagenesPrendaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class PrepararActualizacionPrendaService
{
    public function __construct(
        private readonly ProcesarImagenesPrendaService $procesarImagenesService,
        private readonly EliminarProcesosListaUseCase $eliminarProcesosListaUseCase,
    ) {
    }

    public function preparar(Request $request, int $pedidoId, array $validated): array
    {
        if (!empty($validated['fotosTelas']) && empty($validated['fotos_telas'])) {
            $validated['fotos_telas'] = $validated['fotosTelas'];
        }

        if ($request->has('imagenes_a_eliminar') && is_string($request->input('imagenes_a_eliminar'))) {
            $request->merge([
                'imagenes_a_eliminar' => json_decode((string) $request->input('imagenes_a_eliminar'), true),
            ]);
        }

        $imgs = $this->procesarImagenesService->procesarParaActualizar($request, $pedidoId);

        $procesosAEliminar = $this->normalizarArray($request->input('procesos_a_eliminar'));
        $procesosAEliminar = array_values(array_unique(array_map('intval', $procesosAEliminar)));
        $procesosAEliminar = array_values(array_filter($procesosAEliminar, fn ($id) => $id > 0));

        // Regla canónica: "eliminar" tiene prioridad sobre "actualizar".
        // Si un proceso viene en procesos_a_eliminar, lo quitamos del payload de procesos
        // para evitar que el updater lo recree/retoque en el mismo request.
        if (!empty($procesosAEliminar)) {
            $procesosPayloadNormalizado = $this->normalizarProcesosPayload($validated['procesos'] ?? null);
            if (!empty($procesosPayloadNormalizado)) {
                $procesosPayloadFiltrado = array_values(array_filter(
                    $procesosPayloadNormalizado,
                    function ($proceso) use ($procesosAEliminar) {
                        if (!is_array($proceso) || !isset($proceso['id'])) {
                            return true;
                        }
                        return !in_array((int) $proceso['id'], $procesosAEliminar, true);
                    }
                ));

                $validated['procesos'] = is_string($validated['procesos'] ?? null)
                    ? json_encode($procesosPayloadFiltrado)
                    : $procesosPayloadFiltrado;

                Log::info('[PrepararActualizacionPrendaService] Reconciliacion delete>update aplicada', [
                    'procesos_a_eliminar' => $procesosAEliminar,
                    'procesos_payload_original_count' => count($procesosPayloadNormalizado),
                    'procesos_payload_filtrado_count' => count($procesosPayloadFiltrado),
                ]);
            }
        }

        if (!empty($procesosAEliminar)) {
            Log::info('[PrepararActualizacionPrendaService] Eliminando procesos marcados', [
                'cantidad' => count($procesosAEliminar),
                'ids' => $procesosAEliminar,
            ]);
            $this->eliminarProcesosListaUseCase->ejecutar($procesosAEliminar);
        }

        if ($request->has('asignaciones_colores')) {
            $asignacionesInput = $request->input('asignaciones_colores');
            $validated['asignaciones_colores'] = is_string($asignacionesInput)
                ? json_decode($asignacionesInput, true)
                : $asignacionesInput;

            if (is_null($validated['asignaciones_colores'])) {
                $validated['asignaciones_colores'] = [];
            }
        }

        // FIX: cuando el frontend envía imágenes por `fotos_tela[]` (wizard color/talla),
        // esas rutas procesadas deben reflejarse también en `asignaciones_colores.*.colores.*.imagen_ruta`
        // para que PrendaAsignacionesColoresUpdaterService las persista en `prenda_pedido_talla_colores`.
        if (
            !empty($validated['asignaciones_colores'])
            && !empty($imgs['fotos_telas_procesadas'])
            && !empty($validated['fotos_telas'])
        ) {
            $this->inyectarRutasFotosTelaEnAsignaciones(
                $validated['asignaciones_colores'],
                $validated['fotos_telas'],
                $imgs['fotos_telas_procesadas']
            );
        }

        return [
            'validated' => $validated,
            'imagenes_guardadas' => $imgs['imagenes_guardadas'],
            'imagenes_existentes' => $imgs['imagenes_existentes'],
            'imagenes_a_eliminar' => $imgs['imagenes_a_eliminar'],
            'fotos_telas_procesadas' => $imgs['fotos_telas_procesadas'],
            'fotos_proceso_nuevo' => $imgs['fotos_proceso_nuevo'],
            'fotos_proceso_tallas_nuevo' => $imgs['fotos_proceso_tallas_nuevo'],
            'fotos_color_procesadas' => $imgs['fotos_color_procesadas'],
        ];
    }

    /**
     * Sincroniza rutas de `fotos_telas_procesadas` hacia `asignaciones_colores`.
     * Se apoya en metadatos de `fotos_telas` (color/tela) para ubicar el color correcto.
     *
     * @param array<string, mixed> $asignaciones
     * @param mixed $fotosTelasRaw
     * @param array<int, array<string, mixed>> $fotosTelasProcesadas
     */
    private function inyectarRutasFotosTelaEnAsignaciones(array &$asignaciones, mixed $fotosTelasRaw, array $fotosTelasProcesadas): void
    {
        $fotosTelas = is_string($fotosTelasRaw)
            ? (json_decode($fotosTelasRaw, true) ?? [])
            : (is_array($fotosTelasRaw) ? $fotosTelasRaw : []);

        if (empty($fotosTelas)) {
            return;
        }

        $placeholdersNuevos = [];
        foreach ($fotosTelas as $fotoMeta) {
            if (!is_array($fotoMeta)) {
                continue;
            }

            $id = $fotoMeta['id'] ?? null;
            $ruta = $fotoMeta['ruta_original'] ?? $fotoMeta['ruta_webp'] ?? null;

            // Solo placeholders de archivos NUEVOS (sin id/ruta) consumen rutas procesadas por índice.
            if (!empty($id) || !empty($ruta)) {
                continue;
            }

            $placeholdersNuevos[] = $fotoMeta;
        }

        if (empty($placeholdersNuevos) || empty($fotosTelasProcesadas)) {
            return;
        }

        $rutaAsignadas = 0;

        foreach ($placeholdersNuevos as $idx => $fotoMeta) {
            $rutaProcesada = $fotosTelasProcesadas[$idx]['ruta_webp'] ?? $fotosTelasProcesadas[$idx]['ruta_original'] ?? null;
            if (!$rutaProcesada) {
                continue;
            }

            $colorIdMeta = (int) ($fotoMeta['color_id'] ?? 0);
            $telaIdMeta = (int) ($fotoMeta['tela_id'] ?? 0);
            $colorNombreMeta = $this->normalizarTexto($fotoMeta['color_nombre'] ?? '');
            $telaNombreMeta = $this->normalizarTexto($fotoMeta['tela_nombre'] ?? '');

            foreach ($asignaciones as &$asignacion) {
                if (!is_array($asignacion)) {
                    continue;
                }

                $telaAsignacionNombre = $this->normalizarTexto($asignacion['tela'] ?? $asignacion['tela_nombre'] ?? '');
                $telaAsignacionId = (int) ($asignacion['tela_id'] ?? 0);
                $colores = $asignacion['colores'] ?? null;
                if (!is_array($colores)) {
                    continue;
                }

                foreach ($colores as &$colorData) {
                    if (!is_array($colorData)) {
                        continue;
                    }

                    if (!empty($colorData['imagen_ruta'])) {
                        continue;
                    }

                    $colorAsignacionId = (int) ($colorData['color_id'] ?? 0);
                    $colorAsignacionNombre = $this->normalizarTexto($colorData['nombre'] ?? $colorData['color_nombre'] ?? '');

                    $matchPorId = $colorIdMeta > 0
                        && $telaIdMeta > 0
                        && $colorAsignacionId === $colorIdMeta
                        && $telaAsignacionId === $telaIdMeta;

                    $matchPorNombre = !empty($colorNombreMeta)
                        && !empty($telaNombreMeta)
                        && $colorAsignacionNombre === $colorNombreMeta
                        && $telaAsignacionNombre === $telaNombreMeta;

                    if (!$matchPorId && !$matchPorNombre) {
                        continue;
                    }

                    $colorData['imagen_ruta'] = $rutaProcesada;
                    $rutaAsignadas++;
                    continue 3;
                }
                unset($colorData);
            }
            unset($asignacion);
        }

        if ($rutaAsignadas > 0) {
            Log::info('[PrepararActualizacionPrendaService] Rutas de fotos_tela inyectadas en asignaciones_colores', [
                'rutas_asignadas' => $rutaAsignadas,
                'placeholders_nuevos' => count($placeholdersNuevos),
                'archivos_procesados' => count($fotosTelasProcesadas),
            ]);
        }
    }

    private function normalizarTexto(mixed $valor): string
    {
        if (!is_string($valor)) {
            return '';
        }

        $valor = trim($valor);
        if ($valor === '') {
            return '';
        }

        $sinAcentos = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
        if ($sinAcentos === false) {
            $sinAcentos = $valor;
        }

        return strtoupper($sinAcentos);
    }

    private function normalizarArray(mixed $input): array
    {
        if (is_array($input)) {
            return $input;
        }

        if (is_string($input)) {
            $decoded = json_decode($input, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function extraerIdsProcesosDesdePayload(mixed $procesosRaw): array
    {
        if (is_string($procesosRaw)) {
            $decoded = json_decode($procesosRaw, true);
            $procesosRaw = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($procesosRaw)) {
            return [];
        }

        $ids = [];
        foreach ($procesosRaw as $proceso) {
            if (!is_array($proceso)) {
                continue;
            }
            if (!isset($proceso['id'])) {
                continue;
            }
            $id = (int) $proceso['id'];
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function normalizarProcesosPayload(mixed $procesosRaw): array
    {
        if (is_string($procesosRaw)) {
            $decoded = json_decode($procesosRaw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($procesosRaw) ? $procesosRaw : [];
    }
}
