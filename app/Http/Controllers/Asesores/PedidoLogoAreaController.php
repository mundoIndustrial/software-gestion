<?php

namespace App\Http\Controllers\Asesores;

use App\Models\LogoPedido;
use App\Models\ProcesosPedidosLogo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * PedidoLogoAreaController
 * 
 * Controla los cambios de área/estado de pedidos logo
 */
class PedidoLogoAreaController extends Controller
{
    /**
     * Cambiar el área de un pedido logo
     */
    public function cambiarArea(Request $request, $logoPedidoId): JsonResponse
    {
        try {
            $logoPedido = LogoPedido::findOrFail($logoPedidoId);

            $validated = $request->validate([
                'area' => 'required|in:Creacion de orden,pendiente_confirmar_diseño,en_diseño,logo,estampado',
                'observaciones' => 'nullable|string|max:1000'
            ]);

            // Crear nuevo registro de proceso
            $proceso = ProcesosPedidosLogo::cambiarArea(
                $logoPedidoId,
                $validated['area'],
                $validated['observaciones'] ?? null,
                auth()->id()
            );

            \Log::info('✅ Área de pedido logo cambiada', [
                'logo_pedido_id' => $logoPedidoId,
                'numero_pedido' => $logoPedido->numero_pedido,
                'nueva_area' => $validated['area'],
                'usuario' => auth()->user()->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Área actualizada correctamente',
                'area' => $validated['area'],
                'fecha' => $proceso->fecha_entrada->format('d/m/Y H:i')
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error al cambiar área del pedido logo', [
                'error' => $e->getMessage(),
                'logo_pedido_id' => $logoPedidoId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el historial de áreas de un pedido logo
     */
    public function obtenerHistorial($logoPedidoId): JsonResponse
    {
        try {
            $logoPedido = LogoPedido::with(['procesos' => function($q) {
                $q->with('usuario')->orderBy('created_at', 'desc');
            }])->findOrFail($logoPedidoId);

            $historial = $logoPedido->procesos->map(function($proceso) {
                return [
                    'id' => $proceso->id,
                    'area' => $proceso->area,
                    'observaciones' => $proceso->observaciones,
                    'fecha_entrada' => $proceso->fecha_entrada->format('d/m/Y H:i'),
                    'usuario' => $proceso->usuario?->name ?? 'Sistema'
                ];
            });

            return response()->json([
                'success' => true,
                'numero_pedido' => $logoPedido->numero_pedido,
                'area_actual' => ProcesosPedidosLogo::obtenerAreaActual($logoPedidoId),
                'historial' => $historial
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Obtener todas las áreas disponibles
     */
    public function obtenerAreas(): JsonResponse
    {
        $areas = [
            'Creacion de orden',
            'pendiente_confirmar_diseño',
            'en_diseño',
            'logo',
            'estampado'
        ];

        return response()->json([
            'success' => true,
            'areas' => $areas
        ]);
    }
}
