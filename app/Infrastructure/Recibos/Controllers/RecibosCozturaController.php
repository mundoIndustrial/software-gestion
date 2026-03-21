<?php

namespace App\Infrastructure\Recibos\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Recibos\RecibosCozturaApplicationService;
use Illuminate\Http\Request;

/**
 * Web Controller para Recibos de Costura
 * 
 * Responsabilidades:
 * - Renderizar vistas con datos
 * - Manejar autenticación y autorización
 * - Validar permisos de usuario
 * 
 * Stack:
 * - Application Service: RecibosCozturaApplicationService
 */
class RecibosCozturaController extends Controller
{
    protected $recibosService;

    public function __construct(RecibosCozturaApplicationService $recibosService)
    {
        $this->recibosService = $recibosService;
        $this->middleware('auth');
        $this->middleware('verified');
    }

    /**
     * GET /registros/recibos-costura
     * Mostrar página de recibos de costura
     */
    public function index(Request $request)
    {
        try {
            // Si es AJAX, retornar JSON
            if ($request->ajax() || $request->wantsJson()) {
                $filtros = [
                    'estados' => $request->input('estados', []),
                    'areas' => $request->input('areas', []),
                    'clientes' => $request->input('clientes', []),
                    'descripcion' => $request->input('descripcion'),
                    'numero_recibo' => $request->input('numero_recibo'),
                    'page' => $request->input('page', 1),
                    'per_page' => $request->input('per_page', 50),
                ];

                $resultado = $this->recibosService->obtenerRecibos($filtros);

                return response()->json($resultado);
            }

            // Renderizar vista con datos
            $filtros = [
                'page' => $request->input('page', 1),
                'per_page' => 50,
                'sort_by' => 'created_at',
                'sort_dir' => 'desc',
            ];

            $resultado = $this->recibosService->obtenerRecibos($filtros);
            $opciones = $this->recibosService->obtenerOpcionesFilttro();

            return view('registros.recibos-costura', [
                'recibos' => $resultado['datos'],
                'paginacion' => $resultado['paginacion'],
                'totalCantidadGlobal' => $resultado['totalCantidadGlobal'],
                'filterOptions' => $opciones,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener recibos de costura: ' . $e->getMessage());

            return view('registros.recibos-costura', [
                'recibos' => [],
                'paginacion' => [],
                'totalCantidadGlobal' => 0,
                'filterOptions' => [],
                'error' => 'Error al cargar los recibos. Por favor intenta nuevamente.'
            ]);
        }
    }

    /**
     * GET /registros/{pedido}/recibos-datos
     * Obtener datos de recibos para un pedido específico
     * (Mantener compatibilidad con lógica existente en frontend)
     */
    public function obtenerDatos($pedidoId)
    {
        try {
            $pedido = \App\Models\PedidoProduccion::findOrFail($pedidoId);

            // Cargar relaciones y verificar que existan
            $cliente = $pedido->cliente()->first();
            $prendas = $pedido->prendas()->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $cliente?->nombre,
                    'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega?->format('Y-m-d'),
                    'prendas' => $prendas->toArray(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        }
    }

    /**
     * GET /registros/{pedido}/consecutivo-costura
     * Obtener consecutivo de costura para una prenda
     * (Mantener compatibilidad con modal de seguimiento)
     */
    public function obtenerConsecutivoCostura($pedidoId, Request $request)
    {
        try {
            $prendaId = $request->input('prenda_id');

            $recibo = \App\Models\ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoId)
                ->with(['pedido.prendas', 'procesos'])
                ->first();

            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay recibos para este pedido'
                ], 404);
            }

            $area = $recibo->procesos->first()?->area ?? 'COSTURA';

            return response()->json([
                'success' => true,
                'consecutivo' => $recibo->consecutivo_actual,
                'area' => $area,
                'fecha_creacion' => $recibo->created_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener consecutivo'
            ], 500);
        }
    }
}
