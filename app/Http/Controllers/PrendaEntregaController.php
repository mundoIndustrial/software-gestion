<?php

namespace App\Http\Controllers;

use App\Models\PrendaEntrega;
use App\Models\PrendaPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PrendaEntregaController extends Controller
{
    /**
     * Actualiza el estado de entrega de una prenda
     * 
     * @param Request $request
     * @param int $prendaPedidoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleEntrega(Request $request, $prendaPedidoId)
    {
        try {
            $request->validate([
                'entregado' => 'required|boolean',
            ]);

            // Verificar que la prenda existe
            $prendaPedido = PrendaPedido::findOrFail($prendaPedidoId);

            // Buscar o crear el registro de entrega
            $entrega = PrendaEntrega::firstOrNew([
                'prenda_pedido_id' => $prendaPedidoId,
            ]);

            $entrega->entregado = $request->entregado;
            $entrega->fecha_entrega = $request->entregado ? now() : null;
            $entrega->usuario_id = Auth::id();
            $entrega->save();

            Log::info('Estado de entrega actualizado', [
                'prenda_pedido_id' => $prendaPedidoId,
                'entregado' => $request->entregado,
                'usuario_id' => Auth::id(),
                'fecha_entrega' => $entrega->fecha_entrega,
            ]);

            return response()->json([
                'success' => true,
                'message' => $request->entregado 
                    ? 'Prenda marcada como entregada' 
                    : 'Prenda marcada como no entregada',
                'data' => [
                    'entregado' => $entrega->entregado,
                    'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
                    'usuario' => $entrega->usuario?->name,
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prenda no encontrada',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error al actualizar estado de entrega', [
                'prenda_pedido_id' => $prendaPedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado de entrega',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene el estado de entrega de una prenda
     * 
     * @param int $prendaPedidoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerEstado($prendaPedidoId)
    {
        try {
            $entrega = PrendaEntrega::where('prenda_pedido_id', $prendaPedidoId)->first();

            if (!$entrega) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'entregado' => false,
                        'fecha_entrega' => null,
                        'usuario' => null,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'entregado' => $entrega->entregado,
                    'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
                    'usuario' => $entrega->usuario?->name,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estado de entrega', [
                'prenda_pedido_id' => $prendaPedidoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado de entrega',
            ], 500);
        }
    }
}
