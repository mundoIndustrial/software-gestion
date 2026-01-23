<?php

namespace App\Http\Controllers\API;

use App\Models\Pedido;
use App\Models\HistorialCambiosPedido;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

/**
 * =====================================================
 * CONTROLADOR DE CARTERA - PEDIDOS
 * =====================================================
 * Este es un ejemplo de implementación para que funcione
 * la vista cartera_pedidos.blade.php
 * 
 * NO INCLUIR EN PRODUCCIÓN HASTA QUE SEA COMPLETADO
 */
class CarterapedidoController extends Controller
{
    /**
     * Obtener pedidos por estado (filtrable)
     * 
     * GET /api/pedidos?estado=pendiente_cartera
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validar permisos
            if (!auth()->user()->hasAnyRole(['cartera', 'admin'])) {
                return response()->json([
                    'message' => 'No tienes permiso para acceder a este recurso',
                    'error' => 'Acceso denegado'
                ], 403);
            }

            // Obtener filtro de estado
            $estado = $request->input('estado', 'pendiente_cartera');
            
            // Validar estado permitido
            $estadosPermitidos = ['pendiente_cartera', 'aprobado', 'rechazado'];
            if (!in_array($estado, $estadosPermitidos)) {
                return response()->json([
                    'message' => 'Estado no válido',
                    'error' => "Estados permitidos: " . implode(', ', $estadosPermitidos)
                ], 400);
            }

            // Convertir estado a formato de BD
            $estadoBD = $this->convertirEstadoABD($estado);

            // Consulta base
            $query = Pedido::with(['asesora'])
                ->where('estado', $estadoBD)
                ->orderBy('created_at', 'desc');

            // Aplicar filtros adicionales
            if ($request->filled('cliente')) {
                $query->where('cliente', 'like', '%' . $request->input('cliente') . '%');
            }

            if ($request->filled('numero_pedido')) {
                $query->where('numero_pedido', 'like', '%' . $request->input('numero_pedido') . '%');
            }

            // Paginación
            $perPage = $request->input('per_page', 50);
            $pedidos = $query->paginate($perPage);

            // Formatear respuesta
            $data = $pedidos->items();
            $data = array_map(function ($pedido) {
                return $this->formatearPedido($pedido);
            }, $data);

            return response()->json([
                'data' => $data,
                'total' => $pedidos->total(),
                'per_page' => $pedidos->perPage(),
                'current_page' => $pedidos->currentPage(),
                'last_page' => $pedidos->lastPage(),
                'message' => 'Pedidos obtenidos correctamente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error en CarterapedidoController@index: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al obtener los pedidos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar un pedido
     * 
     * POST /api/pedidos/{id}/aprobar
     * Body: { pedido_id, accion }
     */
    public function aprobar(Request $request, $id): JsonResponse
    {
        try {
            // Validar permisos
            if (!auth()->user()->hasAnyRole(['cartera', 'admin'])) {
                return response()->json([
                    'message' => 'No tienes permiso para aprobar pedidos',
                    'error' => 'Acceso denegado'
                ], 403);
            }

            // Encontrar el pedido
            $pedido = Pedido::find($id);
            if (!$pedido) {
                return response()->json([
                    'message' => 'Pedido no encontrado',
                    'error' => "El pedido con ID $id no existe"
                ], 404);
            }

            // Validar que está en estado correcto
            if ($pedido->estado !== 'Pendiente cartera') {
                return response()->json([
                    'message' => 'No se puede aprobar este pedido',
                    'error' => "El pedido ya está en estado: {$pedido->estado}"
                ], 400);
            }

            // Actualizar estado
            $pedido->estado = 'Aprobado por Cartera';
            $pedido->aprobado_por_cartera_en = now();
            $pedido->aprobado_por_usuario_cartera = auth()->id();
            $pedido->save();

            // Registrar en historial
            HistorialCambiosPedido::create([
                'pedido_id' => $pedido->id,
                'estado_anterior' => 'Pendiente cartera',
                'estado_nuevo' => 'Aprobado por Cartera',
                'usuario_id' => auth()->id(),
                'rol_usuario' => 'cartera',
                'comentario' => 'Pedido aprobado por Cartera',
                'fecha_cambio' => now()
            ]);

            // TODO: Enviar notificación al cliente y al asesor

            return response()->json([
                'message' => 'Pedido aprobado correctamente',
                'data' => $this->formatearPedido($pedido),
                'success' => true
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error en CarterapedidoController@aprobar: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al aprobar el pedido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar un pedido
     * 
     * POST /api/pedidos/{id}/rechazar
     * Body: { pedido_id, motivo, accion }
     */
    public function rechazar(Request $request, $id): JsonResponse
    {
        try {
            // Validar permisos
            if (!auth()->user()->hasAnyRole(['cartera', 'admin'])) {
                return response()->json([
                    'message' => 'No tienes permiso para rechazar pedidos',
                    'error' => 'Acceso denegado'
                ], 403);
            }

            // Validar datos
            $validated = $request->validate([
                'motivo' => 'required|string|min:10|max:1000'
            ], [
                'motivo.required' => 'El motivo es requerido',
                'motivo.min' => 'El motivo debe tener al menos 10 caracteres',
                'motivo.max' => 'El motivo no puede exceder 1000 caracteres'
            ]);

            // Encontrar el pedido
            $pedido = Pedido::find($id);
            if (!$pedido) {
                return response()->json([
                    'message' => 'Pedido no encontrado',
                    'error' => "El pedido con ID $id no existe"
                ], 404);
            }

            // Validar que está en estado correcto
            if ($pedido->estado !== 'Pendiente cartera') {
                return response()->json([
                    'message' => 'No se puede rechazar este pedido',
                    'error' => "El pedido ya está en estado: {$pedido->estado}"
                ], 400);
            }

            // Actualizar estado
            $pedido->estado = 'Rechazado por Cartera';
            $pedido->rechazado_por_cartera_en = now();
            $pedido->rechazado_por_usuario_cartera = auth()->id();
            $pedido->motivo_rechazo_cartera = $validated['motivo'];
            $pedido->save();

            // Registrar en historial
            HistorialCambiosPedido::create([
                'pedido_id' => $pedido->id,
                'estado_anterior' => 'Pendiente cartera',
                'estado_nuevo' => 'Rechazado por Cartera',
                'usuario_id' => auth()->id(),
                'rol_usuario' => 'cartera',
                'comentario' => 'Pedido rechazado por Cartera. Motivo: ' . $validated['motivo'],
                'fecha_cambio' => now()
            ]);

            // TODO: Enviar notificación al cliente con el motivo del rechazo

            return response()->json([
                'message' => 'Pedido rechazado correctamente',
                'data' => array_merge($this->formatearPedido($pedido), [
                    'motivo_rechazo' => $validated['motivo'],
                    'notificacion_enviada' => false  // TODO: Cambiar a true cuando se implemente
                ]),
                'success' => true
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Error en CarterapedidoController@rechazar: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Error al rechazar el pedido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Convertir estado de API a formato de BD
     */
    private function convertirEstadoABD(string $estado): string
    {
        $mapping = [
            'pendiente_cartera' => 'Pendiente cartera',
            'aprobado' => 'Aprobado por Cartera',
            'rechazado' => 'Rechazado por Cartera'
        ];

        return $mapping[$estado] ?? 'Pendiente cartera';
    }

    /**
     * Formatear pedido para respuesta API
     */
    private function formatearPedido($pedido): array
    {
        return [
            'id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente,
            'estado' => $pedido->estado,
            'fecha_de_creacion_de_orden' => $pedido->created_at?->format('Y-m-d H:i:s'),
            'asesora' => $pedido->asesora ? [
                'id' => $pedido->asesora->id,
                'name' => $pedido->asesora->name
            ] : null,
            'forma_de_pago' => $pedido->forma_de_pago ?? null,
            'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega?->format('Y-m-d'),
            'aprobado_por_cartera_en' => $pedido->aprobado_por_cartera_en?->format('Y-m-d H:i:s'),
            'rechazado_por_cartera_en' => $pedido->rechazado_por_cartera_en?->format('Y-m-d H:i:s'),
        ];
    }
}
