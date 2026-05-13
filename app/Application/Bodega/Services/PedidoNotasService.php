<?php

namespace App\Application\Bodega\Services;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PedidoNotasService
{
    public function __construct(
        private BodegaNotaService $notaService
    ) {}

    public function guardarNota(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'numero_pedido' => 'required|string',
                'talla' => 'required|string',
                'talla_color_id' => 'nullable|integer',
                'row_hash' => 'nullable|string|max:32',
                'pedido_epp_id' => 'nullable|integer',
                'prenda_id' => 'nullable|integer',
                'bodega_detalle_talla_id' => 'nullable|integer',
                'contenido' => 'required|string|max:5000',
            ]);

            return $this->notaService->guardarNota($validated, $request);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en guardarNota: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la nota: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerNotas(Request $request, $numero_pedido = null, $talla = null): JsonResponse
    {
        try {
            if ($numero_pedido && $talla) {
                $validated = [
                    'numero_pedido' => $numero_pedido,
                    'talla' => $talla,
                ];
            } else {
                $validated = $request->validate([
                    'numero_pedido' => 'required|string',
                    'talla' => 'required|string',
                    'talla_color_id' => 'nullable|integer',
                ]);
            }

            return $this->notaService->obtenerNotas($validated);

        } catch (\Exception $e) {
            \Log::error('Error en obtenerNotas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las notas'
            ], 500);
        }
    }

    public function actualizarNota(Request $request, $notaId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'contenido' => 'required|string|max:5000',
            ]);

            return $this->notaService->actualizarNota($notaId, $validated);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en actualizarNota: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la nota'
            ], 500);
        }
    }

    public function eliminarNota(Request $request, $notaId): JsonResponse
    {
        try {
            return $this->notaService->eliminarNota($notaId);

        } catch (\Exception $e) {
            \Log::error('Error en eliminarNota: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la nota'
            ], 500);
        }
    }
}
