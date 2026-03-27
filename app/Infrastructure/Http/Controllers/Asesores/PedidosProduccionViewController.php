<?php

namespace App\Infrastructure\Http\Controllers\Asesores;


use Illuminate\Support\Facades\Auth;
use App\Application\Services\Asesores\ObtenerDatosCotizacionService;
use App\Application\Services\Asesores\ObtenerDatosFacturaService;
use App\Application\Services\Asesores\ObtenerDatosPrendaPedidoService;
use App\Application\Services\Asesores\ObtenerPrendaCompletaDesdeCotizacionService;

/**
 * PedidosProduccionViewController
 * Controlador para servir VISTAS HTML de pedidos (NO creacion)
 * El controlador de CREACION es: CrearPedidoEditableController 
 * Responsabilidad: Renderizar vistas y obtener datos para templates
 * NOTA: Los metodos de creacion fueron ELIMINADOS completamente
 * La creacion de pedidos se realiza Unicamente a traves de:
 * POST /asesores/pedidos/crear (CrearPedidoEditableController)
 */
class PedidosProduccionViewController
{
    public function __construct(
        private readonly ObtenerDatosCotizacionService $obtenerDatosCotizacionService,
        private readonly ObtenerDatosFacturaService $obtenerDatosFacturaService,
        private readonly ObtenerDatosPrendaPedidoService $obtenerDatosPrendaPedidoService,
        private readonly ObtenerPrendaCompletaDesdeCotizacionService $obtenerPrendaCompletaDesdeCotizacionService
    ) {
    }

    /**
     * Obtener datos de cotizacion (AJAX)
     */
    public function obtenerDatosCotizacion($cotizacionId)
    {
        try {
            $usuarioId = (int) Auth::id();
            $datos = $this->obtenerDatosCotizacionService->obtenerParaAsesor((int) $cotizacionId, $usuarioId);

            if ($datos === null) {
                return response()->json([
                    'error' => 'cotizacion no encontrada o no tienes permisos para acceder a ella',
                ], 404);
            }

            return response()->json([
                'error' => null,
                'prendas' => $datos['prendas'],
                'logo' => $datos['logo'],
                'tiene_prendas' => $datos['tiene_prendas'],
                'tiene_logo' => $datos['tiene_logo'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en obtenerDatosCotizacion', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error al obtener datos de cotizacion',
                'prendas' => [],
                'logo' => null,
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
     * Obtener datos del pedido para edicion modal
     * GET /asesores/pedidos-produccion/{id}/datos-edicion
     */
    public function obtenerDatosEdicion($pedidoId)
    {
        try {
            $datos = $this->obtenerDatosFacturaService->obtener($pedidoId);
            
            \Log::info('[DATOS-EDICION] Datos cargados', ['pedido_id' => $pedidoId, 'prendas' => count($datos['prendas'] ?? [])]);

            // Retornar en formato que la modal espera
            return response()->json([
                'success' => true,
                'datos' => $datos
            ]);
        } catch (\Exception $e) {
            \Log::error('[DATOS-EDICION] Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar datos del pedido'
            ], 500);
        }
    }

    /**
     * Obtener datos de UNA prenda especifica para edicion
     * Usa Unicamente las 7 tablas transaccionales del pedido
     * GET /asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos
     */
    public function obtenerDatosUnaPrenda($pedidoId, $prendaId)
    {
        try {
            $datos = $this->obtenerDatosPrendaPedidoService->obtenerParaEdicion((int) $pedidoId, (int) $prendaId);

            if ($datos === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prenda no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'prenda' => $datos
            ]);
        } catch (\Exception $e) {
            \Log::error('[PRENDA-DATOS] Error obteniendo datos de prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de la prenda',
            ], 500);
        }
    }
    /**
     * Obtener prenda completa desde cotización (para crear/editar pedido)
     * GET /asesores/pedidos-produccion/obtener-prenda-completa/{cotizacionId}/{prendaId}
     */
    public function obtenerPrendaCompleta($cotizacionId, $prendaId)
    {
        $payload = ['error' => 'Error al obtener prenda completa'];
        $statusCode = 500;

        try {
            $resultado = $this->obtenerPrendaCompletaDesdeCotizacionService->obtener((int) $cotizacionId, (int) $prendaId);
            $status = $resultado['status'] ?? null;

            if ($status === 'cotizacion_no_encontrada') {
                $payload = ['error' => 'cotizacion no encontrada'];
                $statusCode = 404;
            } elseif ($status === 'prenda_no_encontrada') {
                $payload = ['error' => 'Prenda no encontrada'];
                $statusCode = 404;
            } elseif ($status === 'ok') {
                $payload = array_merge([
                    'success' => true,
                ], $resultado['data']);
                $statusCode = 200;
            }
        } catch (\Exception $e) {
            \Log::error('[OBTENER-PRENDA-COMPLETA] Error obteniendo prenda completa', [
                'cotizacion_id' => $cotizacionId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json($payload, $statusCode);
    }
}



