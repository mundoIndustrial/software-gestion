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
