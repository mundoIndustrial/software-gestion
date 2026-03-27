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
}

