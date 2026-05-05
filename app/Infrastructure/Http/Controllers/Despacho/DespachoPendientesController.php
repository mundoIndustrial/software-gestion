<?php

namespace App\Infrastructure\Http\Controllers\Despacho;

use App\Application\Services\Despacho\DespachoPendientesApplicationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DespachoPendientesController extends Controller
{
    public function __construct(
        private readonly DespachoPendientesApplicationService $service,
    ) {
    }

    /**
     * API para obtener pedidos con prendas que se sacan de bodega y NO tienen ningun proceso
     */
    public function obtenerPendientesBodegaSinProcesos(Request $request)
    {
        try {
            $data = $this->service->obtenerPendientesBodegaSinProcesosData((string) $request->query('search', ''));

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos de bodega sin procesos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vista unificada de pendientes de costura y EPP para despacho
     */
    public function pendientesUnificados(Request $request)
    {
        $search = $request->query('search', '');
        $tipo = $request->query('tipo', 'todos');

        return view('despacho.pendientes-unificados', [
            'search' => $search,
            'tipo' => $tipo,
        ]);
    }

    /**
     * Vista de pedidos entregados
     */
    public function entregados(Request $request)
    {
        $search = $request->query('search', '');

        return view('despacho.entregados', [
            'search' => $search,
        ]);
    }

    /**
     * Vista de pedidos anulados
     */
    public function anulados(Request $request)
    {
        $search = $request->query('search', '');

        return view('despacho.anulados', [
            'search' => $search,
        ]);
    }

    /**
     * Vista del historial de todos los pendientes (actuales e históricos)
     */
    public function historialPendientes(Request $request)
    {
        $search = $request->query('search', '');
        $tipo = $request->query('tipo', 'todos');

        return view('despacho.historial-pendientes', [
            'search' => $search,
            'tipo' => $tipo,
        ]);
    }

    /**
     * API para obtener historial de todos los pendientes
     */
    public function obtenerHistorialPendientes(Request $request)
    {
        try {
            $payload = $this->service->obtenerHistorialPendientesData(
                search: (string) $request->query('search', ''),
                tipo: (string) $request->query('tipo', 'todos'),
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 10),
            );

            return response()->json($payload);
        } catch (\Exception $e) {
            \Log::error('[ERROR] Error en obtenerHistorialPendientes:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial de pendientes: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * API para obtener pendientes de costura
     */
    public function obtenerPendientesCostura(Request $request)
    {
        try {
            $data = $this->service->obtenerPendientesCosturaData((string) $request->query('search', ''));

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pendientes de costura: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API para obtener pendientes de EPP
     */
    public function obtenerPendientesEpp(Request $request)
    {
        try {
            $data = $this->service->obtenerPendientesEppData((string) $request->query('search', ''));

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pendientes de EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API para obtener todos los pendientes unificados
     */
    public function obtenerPendientesUnificados(Request $request)
    {
        try {
            $payload = $this->service->obtenerPendientesUnificadosData(
                search: (string) $request->query('search', ''),
                tipo: (string) $request->query('tipo', 'todos'),
                filter: (string) $request->query('filter', ''),
                page: (int) $request->query('page', 1),
                perPage: (int) $request->query('per_page', 10),
            );

            return response()->json($payload);
        } catch (\Exception $e) {
            \Log::error('[ERROR] Error en obtenerPendientesUnificados:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pendientes unificados: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * API para obtener todos los pedidos con estados solicitados (excluyendo completamente entregados en bodega)
     */
    public function obtenerTodosLosPedidos(Request $request)
    {
        return response()->json(
            $this->service->obtenerTodosLosPedidosData((string) $request->query('search', ''))
        );
    }

    /**
     * API para obtener pedidos entregados
     */
    public function obtenerEntregados(Request $request)
    {
        try {
            return response()->json(
                $this->service->obtenerEntregadosData(
                    search: (string) $request->query('search', ''),
                    page: (int) $request->query('page', 1),
                    perPage: (int) $request->query('per_page', 10)
                )
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos entregados: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * API para obtener pedidos anulados
     */
    public function obtenerAnulados(Request $request)
    {
        try {
            return response()->json(
                $this->service->obtenerAnuladosData(
                    search: (string) $request->query('search', ''),
                    page: (int) $request->query('page', 1),
                    perPage: (int) $request->query('per_page', 10)
                )
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos anulados: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Mostrar detalles de pedido pendiente (vista igual a bodega)
     */
    public function showPendienteUnificado($id)
    {
        try {
            $data = $this->service->construirDetallePendienteUnificado((int) $id);

            return view('despacho.show-pendiente-bodega', [
                'pedido' => $data['pedido'],
                'items' => $data['items'],
                'origen' => 'pendientes',
            ]);
        } catch (\Exception $e) {
            \Log::error('[DESPACHO] Error al mostrar detalles del pedido pendiente', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('despacho.pendientes')
                ->with('error', 'Error al cargar el pedido: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalles de pedido desde historial (mostrando todos los items: pendientes + entregados)
     */
    public function showHistorialPendiente($id)
    {
        try {
            $data = $this->service->construirDetallePendienteUnificado((int) $id, true);
            $items = is_array($data['items'] ?? null) ? $data['items'] : [];

            $itemsConFechaPendiente = collect($items)->filter(function ($item) {
                $fechaPendiente = $item['fecha_pendiente'] ?? null;
                return is_string($fechaPendiente)
                    ? trim($fechaPendiente) !== ''
                    : !empty($fechaPendiente);
            })->values()->all();

            $tieneFechaPendiente = !empty($itemsConFechaPendiente);

            if (!$tieneFechaPendiente) {
                return redirect()->route('despacho.historial-pendientes')
                    ->with('error', 'Este pedido no tiene items con fecha pendiente en historial.');
            }

            return view('despacho.show-historial-pendiente-nuevo', [
                'pedido' => $data['pedido'],
                'items' => $itemsConFechaPendiente,
                'origen' => 'historial',
            ]);
        } catch (\Exception $e) {
            \Log::error('[DESPACHO] Error al mostrar detalles del pedido en historial', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('despacho.historial-pendientes')
                ->with('error', 'Error al cargar el pedido: ' . $e->getMessage());
        }
    }
}
