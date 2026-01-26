<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * AsesoresAPIController
 * 
 * ⚠️ DEPRECADO: Este controller está siendo migrado completamente a DDD
 * 
 * Migración completada:
 * - Todos los métodos de catálogos (tipos-manga, tipos-broche, telas, colores) → PedidoController
 * - obtenerDatosEdicion() → PedidoController
 * - obtenerDetalleCompleto() → PedidoController
 * 
 * Este archivo se puede eliminar una vez que todas las rutas sean miradas.
 * 
 * PLAN:
 * - Fase 1: Migrar métodos útiles ✅ COMPLETADO
 * - Fase 2: Actualizar todas las rutas ✅ COMPLETADO
 * - Fase 3: Eliminar este archivo ⏳ PRÓXIMO
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
     * Usa: POST /api/pedidos con PedidoController::store()
     * 
     * @deprecated
     */
    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Esta ruta está deprecada. Usa POST /api/pedidos',
            'nueva_ruta' => 'POST /api/pedidos'
        ], 410);
    }

    /**
     * DEPRECADO: confirm()
     * 
     * Usa: PATCH /api/pedidos/{id}/confirmar
     * 
     * @deprecated
     */
    public function confirm(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Esta ruta está deprecada. Usa PATCH /api/pedidos/{id}/confirmar',
            'nueva_ruta' => 'PATCH /api/pedidos/{id}/confirmar'
        ], 410);
    }

    /**
     * DEPRECADO: anularPedido()
     * 
     * Usa: DELETE /api/pedidos/{id}/cancelar
     * 
     * @deprecated
     */
    public function anularPedido(Request $request, $id)
    {
        return response()->json([
            'success' => false,
            'message' => 'Esta ruta está deprecada. Usa DELETE /api/pedidos/{id}/cancelar',
            'nueva_ruta' => 'DELETE /api/pedidos/{id}/cancelar'
        ], 410);
    }

    /**
     * DEPRECADO: obtenerFotosPrendaPedido()
     * 
     * @deprecated
     */
    public function obtenerFotosPrendaPedido($prendaPedidoId)
    {
        return response()->json([
            'success' => false,
            'message' => 'Esta funcionalidad está siendo refactorizada a DDD',
        ], 501);
    }
}

