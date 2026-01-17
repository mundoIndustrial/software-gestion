<?php

namespace App\Modules\Pedidos\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use App\Models\PedidoEpp;
use App\Services\PedidoEppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoEppController extends Controller
{
    protected $eppService;

    public function __construct(PedidoEppService $eppService)
    {
        $this->eppService = $eppService;
    }

    /**
     * Obtener todos los EPP de un pedido
     */
    public function index(PedidoProduccion $pedido): JsonResponse
    {
        try {
            $epps = $this->eppService->obtenerEppsDelPedido($pedido);

            return response()->json([
                'success' => true,
                'data' => $epps,
                'count' => count($epps)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener EPP del pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guardar EPP en el pedido
     */
    public function store(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        try {
            $validated = $request->validate([
                'epps' => 'required|array',
                'epps.*.epp_id' => 'required|integer|exists:epps,id',
                'epps.*.cantidad' => 'required|integer|min:1',
                'epps.*.tallas_medidas' => 'nullable|array',
                'epps.*.observaciones' => 'nullable|string',
                'epps.*.imagenes' => 'nullable|array',
            ]);

            $epps = $this->eppService->guardarEppsDelPedido($pedido, $validated['epps']);

            return response()->json([
                'success' => true,
                'message' => count($epps) . ' EPP agregados al pedido',
                'data' => $epps
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar EPP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un EPP del pedido
     */
    public function update(Request $request, PedidoProduccion $pedido, PedidoEpp $pedidoEpp): JsonResponse
    {
        try {
            // Verificar que el EPP pertenece al pedido
            if ($pedidoEpp->pedido_produccion_id !== $pedido->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El EPP no pertenece a este pedido'
                ], 403);
            }

            $validated = $request->validate([
                'cantidad' => 'required|integer|min:1',
                'tallas_medidas' => 'nullable|array',
                'observaciones' => 'nullable|string',
            ]);

            $this->eppService->actualizarEpp($pedidoEpp, $validated);

            return response()->json([
                'success' => true,
                'message' => 'EPP actualizado correctamente',
                'data' => $pedidoEpp
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar EPP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un EPP del pedido
     */
    public function destroy(PedidoProduccion $pedido, PedidoEpp $pedidoEpp): JsonResponse
    {
        try {
            // Verificar que el EPP pertenece al pedido
            if ($pedidoEpp->pedido_produccion_id !== $pedido->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El EPP no pertenece a este pedido'
                ], 403);
            }

            $this->eppService->eliminarEpp($pedidoEpp);

            return response()->json([
                'success' => true,
                'message' => 'EPP eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar EPP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener JSON serializado de todos los EPP del pedido
     */
    public function exportarJson(PedidoProduccion $pedido): JsonResponse
    {
        try {
            $json = $this->eppService->serializarEppsAJson($pedido);

            return response()->json([
                'success' => true,
                'json' => $json
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar EPP: ' . $e->getMessage()
            ], 500);
        }
    }
}
