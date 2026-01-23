<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\PedidoController;

/**
 * AsesoresAPIController
 * 
 * ⚠️ DEPRECADO: Este controller está siendo migrado a DDD
 * 
 * Ahora delega toda la lógica a PedidoController (DDD)
 * Mantener solo para compatibilidad temporal
 * 
 * PLAN DE MIGRACIÓN:
 * - Fase 1: Eliminar rutas duplicadas ✅
 * - Fase 2: Usar PedidoController como delegado (ACTUAL)
 * - Fase 3: Eliminar este archivo completamente
 */
class AsesoresAPIController extends Controller
{
    private PedidoController $pedidoController;

    public function __construct(PedidoController $pedidoController)
    {
        $this->pedidoController = $pedidoController;
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
}
