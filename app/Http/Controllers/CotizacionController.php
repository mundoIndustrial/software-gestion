<?php

namespace App\Http\Controllers;

use App\Models\OrdenAsesor;
use App\Models\ProductoPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * CotizacionController - Gestión de cotizaciones con borradores
 * 
 * Responsabilidades:
 * - Listar cotizaciones confirmadas (ordenes_asesor)
 * - Gestionar borradores (órdenes sin confirmar)
 * - Crear, actualizar, confirmar y eliminar borradores
 * - Generar número de pedido al confirmar cotización
 * 
 * Nota: Los procesos de producción se gestionan en PedidosProduccionController
 */
class CotizacionController extends Controller
{
    /**
     * Listar borradores (órdenes sin confirmar)
     */
    public function borradores(Request $request)
    {
        $asesorId = Auth::id();
        
        $query = OrdenAsesor::delAsesor($asesorId)->borradores()->with('productos');

        if ($request->filled('cliente')) {
            $query->where('cliente', 'LIKE', '%' . $request->cliente . '%');
        }

        $borradores = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('asesores.borradores.index', compact('borradores'));
    }
}