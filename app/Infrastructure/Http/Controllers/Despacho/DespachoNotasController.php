<?php

namespace App\Infrastructure\Http\Controllers\Despacho;

use App\Http\Controllers\Controller;
use App\Application\Bodega\Services\BodegaNotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DespachoNotasController extends Controller
{
    public function __construct(
        private BodegaNotaService $bodegaNotaService,
    ) {
    }

    public function obtenerNotasBodega(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'talla' => 'required|string',
                'talla_color_id' => 'nullable|integer',
            ]);

            return $this->bodegaNotaService->obtenerNotas($validated);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notas',
            ], 500);
        }
    }

    public function guardarNotaBodega(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'talla' => 'required|string',
                'talla_color_id' => 'nullable|integer',
                'contenido' => 'required|string|max:5000',
            ]);

            return $this->bodegaNotaService->guardarNota($validated, $request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la nota: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function actualizarNotaBodega(Request $request, int $notaId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'contenido' => 'required|string|max:5000',
            ]);

            return $this->bodegaNotaService->actualizarNota($notaId, $validated);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la nota',
            ], 500);
        }
    }

    public function eliminarNotaBodega(Request $request, int $notaId): JsonResponse
    {
        try {
            return $this->bodegaNotaService->eliminarNota($notaId);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la nota',
            ], 500);
        }
    }
}

