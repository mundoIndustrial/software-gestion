<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * AsesoresAPIController
 * 
 * ⚠️ DEPRECADO: Este controller está siendo migrado a DDD
 * 
 * Ahora delega toda la lógica a PedidoController (DDD)
 * Mantener solo para compatibilidad temporal
 * 
 * PLAN DE MIGRACIÓN:
 * - Fase 1: Eliminar rutas duplicadas 
 * - Fase 2: Usar PedidoController como delegado (ACTUAL)
 * - Fase 3: Eliminar este archivo completamente
 */
class AsesoresAPIController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * DEPRECADO: store()
     * 
     * Ahora debes usar: POST /api/pedidos
     * 
     * @deprecated Usa PedidoController::store() en /api/pedidos
     */
    public function store(Request $request)
    {
        // TODO: Migrar la lógica legacy de CrearPedidoService a un Use Case DDD
        // Por ahora, retornar error indicando usar nueva ruta
        return response()->json([
            'success' => false,
            'message' => 'Esta ruta está deprecada. Usa POST /api/pedidos en su lugar.',
            'nueva_ruta' => 'POST /api/pedidos'
        ], 410); // 410 Gone
    }

    /**
     * DEPRECADO: confirm()
     * 
     * Ahora debes usar: PATCH /api/pedidos/{id}/confirmar
     * 
     * @deprecated Usa PedidoController::confirmar() en /api/pedidos
     */
    public function confirm(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Esta ruta está deprecada. Usa PATCH /api/pedidos/{id}/confirmar en su lugar.',
            'nueva_ruta' => 'PATCH /api/pedidos/{id}/confirmar'
        ], 410); // 410 Gone
    }

    /**
     * DEPRECADO: anularPedido()
     * 
     * Ahora debes usar: DELETE /api/pedidos/{id}/cancelar
     * 
     * @deprecated Usa PedidoController::cancelar() en /api/pedidos
     */
    public function anularPedido(Request $request, $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Esta ruta está deprecada. Usa DELETE /api/pedidos/{id}/cancelar en su lugar.',
            'nueva_ruta' => 'DELETE /api/pedidos/{id}/cancelar'
        ], 410); // 410 Gone
    }

    /**
     * DEPRECADO: obtenerDatosRecibos()
     * 
     * Ahora debes usar: GET /asesores/pedidos/{id}/recibos-datos
     * que delegará a PedidoController::obtenerDetalleCompleto()
     * 
     * @deprecated 
     */
    public function obtenerDatosRecibos($id)
    {
        // Esta ruta ahora está en PedidoController
        return response()->json([
            'success' => false,
            'message' => 'Migrado a PedidoController::obtenerDetalleCompleto()',
        ], 410);
    }

    /**
     * DEPRECADO: obtenerFotosPrendaPedido()
     * 
     * @deprecated Requiere refactorización a DDD
     */
    public function obtenerFotosPrendaPedido($prendaPedidoId)
    {
        return response()->json([
            'success' => false,
            'message' => 'Esta funcionalidad está siendo refactorizada a DDD',
        ], 501); // Not Implemented
    }

    /**
     * Obtener tipos de broche/botón disponibles
     * 
     * Endpoint: GET /asesores/api/tipos-broche-boton
     * Respuesta: Array de tipos de broche/botón con su ID
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerTiposBrocheBoton()
    {
        try {
            $tipos = \App\Models\TipoBrocheBoton::where('activo', true)
                ->select('id', 'nombre')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ]);
        } catch (\Exception $e) {
            \Log::error('[AsesoresAPIController] Error obtener tipos broche/botón: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de broche/botón',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de manga disponibles
     * 
     * Endpoint: GET /asesores/api/tipos-manga
     * Respuesta: Array de tipos de manga con su ID
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerTiposManga()
    {
        try {
            $tipos = \App\Models\TipoManga::where('activo', true)
                ->select('id', 'nombre')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ]);
        } catch (\Exception $e) {
            \Log::error('[AsesoresAPIController] Error obtener tipos manga: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de manga',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear o obtener un tipo de manga por nombre
     * Si no existe, lo crea automáticamente
     * 
     * Endpoint: POST /asesores/api/tipos-manga
     * Request: { "nombre": "manga larga" }
     * Respuesta: { "success": true, "data": { "id": 5, "nombre": "manga larga" } }
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function crearObtenerTipoManga(Request $request)
    {
        try {
            $nombre = trim($request->input('nombre', ''));
            
            if (empty($nombre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre del tipo de manga es requerido'
                ], 400);
            }

            // Buscar si ya existe (case-insensitive)
            $tipo = \App\Models\TipoManga::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])
                ->first();

            // Si no existe, crearlo
            if (!$tipo) {
                $tipo = \App\Models\TipoManga::create([
                    'nombre' => ucfirst(strtolower($nombre)),
                    'activo' => true
                ]);

                \Log::info('[AsesoresAPIController] Nuevo tipo de manga creado', [
                    'id' => $tipo->id,
                    'nombre' => $tipo->nombre
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $tipo,
                'mensaje' => $tipo->wasRecentlyCreated ? 'Tipo creado' : 'Tipo existente'
            ]);

        } catch (\Exception $e) {
            \Log::error('[AsesoresAPIController] Error crear/obtener tipo manga: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear/obtener tipo de manga',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
