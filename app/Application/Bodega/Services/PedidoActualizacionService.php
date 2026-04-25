<?php

namespace App\Application\Bodega\Services;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PedidoActualizacionService
{
    public function __construct(
        private BodegaUpdateService $updateService
    ) {}

    public function actualizarObservaciones(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:recibo_prendas,id',
                'observaciones' => 'nullable|string|max:500',
            ]);

            $result = $this->updateService->actualizarObservaciones(
                $validated['id'],
                $validated['observaciones'] ?? null
            );

            $statusCode = $result['success'] ? 200 : 400;
            return response()->json($result, $statusCode);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en actualizarObservaciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function actualizarFecha(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:recibo_prendas,id',
                'fecha_entrega' => 'required|date',
            ]);

            $result = $this->updateService->actualizarFecha(
                $validated['id'],
                $validated['fecha_entrega']
            );

            $statusCode = $result['success'] ? 200 : 400;
            return response()->json($result, $statusCode);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en actualizarFecha: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function actualizarFechaEntregaDespacho(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'fecha_entrega_despacho' => 'required|date_format:Y-m-d',
            ]);

            $result = $this->updateService->actualizarFechaEntregaDespacho(
                $id,
                $validated['fecha_entrega_despacho']
            );

            $statusCode = $result['success'] ? 200 : 500;
            return response()->json($result, $statusCode);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar fecha de entrega a despacho: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la fecha'
            ], 500);
        }
    }
}
