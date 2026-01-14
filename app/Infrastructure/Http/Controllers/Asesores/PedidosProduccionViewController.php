<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Cotizacion;
use Illuminate\Support\Facades\Auth;

/**
 * PedidosProduccionViewController
 * 
 * Controlador para servir VISTAS HTML de pedidos
 * El controlador API (PedidosProduccionController) maneja solo JSON/CQRS
 * 
 * Responsabilidad: Renderizar vistas y preparar datos para templates
 */
class PedidosProduccionViewController
{
    /**
     * Mostrar formulario para crear pedido desde cotización
     */
    public function crearFormEditable(): View
    {
        // Obtener cotizaciones aprobadas para pedido
        $cotizacionesQuery = Cotizacion::query()
            ->select('id', 'numero_cotizacion', 'cliente_id', 'asesor_id', 'estado')
            ->with('cliente:id,nombre', 'asesor:id,name')
            ->where('estado', 'APROBADO_PARA_PEDIDO')
            ->orderBy('created_at', 'desc');
        
        $cotizaciones = $cotizacionesQuery->get();
        
        // Transformar para el frontend
        $cotizacionesData = $cotizaciones->map(function($cot) {
            return [
                'id' => $cot->id,
                'numero_cotizacion' => $cot->numero_cotizacion,
                'cliente' => $cot->cliente?->nombre ?? 'N/A',
                'asesora' => $cot->asesor?->name ?? 'N/A',
                'estado' => $cot->estado,
            ];
        })->toArray();

        return view('asesores.pedidos.crear-pedido-desde-cotizacion', [
            'cotizacionesData' => $cotizacionesData
        ]);
    }

    /**
     * Mostrar formulario para crear pedido nuevo (sin cotización)
     */
    public function crearFormEditableNuevo(): View
    {
        return view('asesores.pedidos.crear-pedido-nuevo');
    }

    /**
     * Obtener datos de cotización (AJAX)
     */
    public function obtenerDatosCotizacion($cotizacionId)
    {
        try {
            // Obtener cotización con sus relaciones
            $cotizacion = Cotizacion::with([
                'tipoCotizacion:id,nombre',
                'prendas:id,cotizacion_id,prenda_id,cantidad',
                'prendas.prenda:id,nombre',
                'reflectivo:id,cotizacion_id,tipo_reflectivo,cantidad',
                'logoCotizacion:id,cotizacion_id,tipo_logo'
            ])->find($cotizacionId);

            if (!$cotizacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cotización no encontrada'
                ], 404);
            }

            // Formatear datos para el frontend
            $prendas = $cotizacion->prendas->map(function($prenda) {
                return [
                    'id' => $prenda->id,
                    'nombre' => $prenda->prenda?->nombre ?? 'Prenda',
                    'cantidad' => $prenda->cantidad,
                    'tipo' => 'prenda'
                ];
            })->toArray();

            $reflectivo = null;
            if ($cotizacion->reflectivo) {
                $reflectivo = [
                    'id' => $cotizacion->reflectivo->id,
                    'tipo' => $cotizacion->reflectivo->tipo_reflectivo,
                    'cantidad' => $cotizacion->reflectivo->cantidad,
                    'tipo' => 'reflectivo'
                ];
            }

            $logo = null;
            if ($cotizacion->logoCotizacion) {
                $logo = [
                    'id' => $cotizacion->logoCotizacion->id,
                    'tipo' => $cotizacion->logoCotizacion->tipo_logo,
                    'tipo' => 'logo'
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'tipo_cotizacion' => $cotizacion->tipoCotizacion?->nombre ?? 'Desconocido',
                    'prendas' => $prendas,
                    'reflectivo' => $reflectivo,
                    'logo' => $logo,
                    'tiene_prendas' => count($prendas) > 0,
                    'tiene_reflectivo' => $reflectivo !== null,
                    'tiene_logo' => $logo !== null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar plantilla de pedido
     */
    public function plantilla($id)
    {
        return view('asesores.pedidos.show', [
            'pedido_id' => $id
        ]);
    }

    /**
     * Crear pedido desde cotización (formulario)
     */
    public function crearDesdeCotizacion(Request $request, $cotizacionId)
    {
        // Validar y procesar
        return response()->json([
            'success' => true,
            'message' => 'Use la ruta API POST /api/pedidos'
        ]);
    }

    /**
     * Crear pedido sin cotización (formulario)
     */
    public function crearSinCotizacion(Request $request)
    {
        // Validar y procesar
        return response()->json([
            'success' => true,
            'message' => 'Use la ruta API POST /api/pedidos'
        ]);
    }

    /**
     * Crear prenda sin cotización (AJAX)
     */
    public function crearPrendaSinCotizacion(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Use la ruta API POST /api/pedidos/{id}/prendas'
        ]);
    }

    /**
     * Crear reflectivo sin cotización (AJAX)
     */
    public function crearReflectivoSinCotizacion(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Use la ruta API POST /api/pedidos/{id}/prendas'
        ]);
    }
}
