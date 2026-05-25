<?php

namespace App\Infrastructure\Http\Controllers\Despacho;

use App\Application\Services\Despacho\DespachoControlApplicationService;
use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DespachoControlController extends Controller
{
    public function __construct(
        private readonly DespachoControlApplicationService $service,
    ) {
    }

    /**
     * Listar pedidos disponibles para despacho
     */
    public function index(Request $request)
    {
        $data = $this->service->obtenerListadoIndex(
            (string) $request->input('search', ''),
            $request->input('asesor_id') ? (int) $request->input('asesor_id') : null
        );

        return view('despacho.index', [
            'pedidos' => $data['pedidos'],
            'search' => $data['search'],
        ]);
    }

    /**
     * Mostrar detalle de despacho para un pedido
     */
    public function show(PedidoProduccion $pedido)
    {
        return view('despacho.show', $this->service->obtenerDetallePedido($pedido));
    }

    /**
     * Guardar control de entregas (despacho)
     */
    public function guardarDespacho(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        try {
            $validated = $request->validate([
                'despachos' => 'required|array',
                'despachos.*.tipo' => 'required|string|in:prenda,epp',
                'despachos.*.id' => 'required|integer',
                'despachos.*.talla_id' => 'nullable|integer',
                'despachos.*.genero' => 'nullable|string',
                'cliente_empresa' => 'nullable|string',
                'fecha_hora' => 'nullable|string',
            ]);

            return response()->json(
                $this->service->guardarControlEntregas($pedido, $validated)
            );
        } catch (\Exception $e) {
            Log::error('Error al guardar despacho', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Vista de impresion del control de entregas
     */
    public function printDespacho(PedidoProduccion $pedido)
    {
        return view('despacho.print', $this->service->obtenerDatosPrint($pedido));
    }

    /**
     * Obtener despachos guardados para un pedido
     */
    public function obtenerDespachos(PedidoProduccion $pedido): JsonResponse
    {
        try {
            return response()->json($this->service->obtenerDespachos($pedido));
        } catch (\Exception $e) {
            Log::error('Error al obtener despachos', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener despachos',
            ], 500);
        }
    }

    /**
     * Obtener datos de factura para un pedido
     */
    public function obtenerFacturaDatos(PedidoProduccion $pedido): JsonResponse
    {
        try {
            return response()->json($this->service->obtenerFacturaDatos($pedido));
        } catch (\Exception $e) {
            Log::error('Error al obtener datos de factura', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos',
            ], 500);
        }
    }

    /**
     * Marcar item como entregado
     */
    public function marcarEntregado(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        Log::info('[DespachoController] marcarEntregado llamado', [
            'pedido_id' => $pedido->id,
            'request_data' => $request->all(),
        ]);

        try {
            $validated = $request->validate([
                'tipo_item' => 'required|string|in:prenda,epp',
                'item_id' => 'required|integer',
                'talla_id' => 'nullable|integer',
                'talla_color_id' => 'nullable|integer',
            ]);

            return response()->json($this->service->marcarEntregado($pedido, $validated));
        } catch (\Exception $e) {
            Log::error('Error al marcar como entregado', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar',
            ], 500);
        }
    }

    /**
     * Obtener estado de entregas
     */
    public function obtenerEstadoEntregas(PedidoProduccion $pedido): JsonResponse
    {
        try {
            return response()->json($this->service->obtenerEstadoEntregas($pedido));
        } catch (\Exception $e) {
            Log::error('Error al obtener estado de entregas', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado',
            ], 500);
        }
    }

    /**
     * Deshacer marcado como entregado
     */
    public function deshacerEntregado(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        Log::info('[DespachoController] deshacerEntregado llamado', [
            'pedido_id' => $pedido->id,
            'request_data' => $request->all(),
        ]);

        try {
            $validated = $request->validate([
                'tipo_item' => 'required|string|in:prenda,epp',
                'item_id' => 'required|integer',
                'talla_id' => 'nullable|integer',
                'talla_color_id' => 'nullable|integer',
            ]);

            $payload = $this->service->deshacerEntregado($pedido, $validated);
            $status = (int) ($payload['_status'] ?? 200);
            unset($payload['_status']);

            return response()->json($payload, $status);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error al deshacer entregado', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar',
            ], 500);
        }
    }

    public function guardarAjusteCantidad(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tipo_item' => 'required|string|in:prenda,epp',
                'item_id' => 'required|integer',
                'talla_id' => 'nullable|integer',
                'talla_color_id' => 'nullable|integer',
                'genero' => 'nullable|string|max:50',
                'cantidad_original' => 'required|integer|min:0',
                'cantidad_ajustada' => 'required|integer|min:0',
                'motivo' => 'nullable|string|max:500',
            ]);

            return response()->json(
                $this->service->guardarAjusteCantidad($pedido, $validated)
            );
        } catch (\Throwable $e) {
            Log::error('Error al guardar ajuste de cantidad en despacho', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Marcar todos los items de un pedido como entregados
     */
    public function entregarTodo(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        Log::info('[DespachoController] entregarTodo llamado', [
            'pedido_id' => $pedido->id,
            'numero_pedido' => $pedido->numero_pedido,
        ]);

        try {
            return response()->json($this->service->entregarTodo($pedido));
        } catch (\Exception $e) {
            Log::error('Error al marcar pedido como entregado completamente', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar: ' . $e->getMessage(),
            ], 500);
        }
    }
}
