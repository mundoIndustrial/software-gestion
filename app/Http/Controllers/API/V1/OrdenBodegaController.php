<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Domain\Bodega\Services\CrearOrdenBodegaService;
use App\Domain\Bodega\Services\ActualizarEstadoOrdenBodegaService;
use App\Domain\Bodega\Services\CancelarOrdenBodegaService;
use App\Domain\Bodega\Services\ObtenerOrdenBodegaService;

/**
 * Api/V1/OrdenBodegaController - DDD HTTP Layer
 * 
 * Responsabilidad: Exponer la capa Domain mediante HTTP
 * - Operaciones en términos del Domain Model
 * - Usar Value Objects y Aggregates
 * - Serializar Domain Entities para HTTP
 * 
 * Dependencias: 4 Application Services (inyección limpia)
 * Líneas: ~200
 */
class OrdenBodegaController extends Controller
{
    public function __construct(
        private CrearOrdenBodegaService $crearService,
        private ActualizarEstadoOrdenBodegaService $actualizarEstadoService,
        private CancelarOrdenBodegaService $cancelarService,
        private ObtenerOrdenBodegaService $obtenerService
    ) {}

    /**
     * Listar todas las órdenes de bodega (DDD serialization)
     */
    public function index()
    {
        try {
            $ordenes = $this->obtenerService->obtenerTodas();

            return response()->json([
                'success' => true,
                'data' => $ordenes->map(fn($o) => $this->serializarOrden($o)),
                'count' => count($ordenes)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener órdenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una orden específica por número
     */
    public function show(int $numero)
    {
        try {
            $ordenData = $this->obtenerService->obtenerPorNumero($numero);

            if (!$ordenData) {
                return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $ordenData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Filtrar órdenes por cliente
     */
    public function porCliente(string $cliente)
    {
        try {
            $ordenes = $this->obtenerService->obtenerPorCliente($cliente);

            return response()->json([
                'success' => true,
                'data' => $ordenes->map(fn($o) => $this->serializarOrden($o)),
                'count' => count($ordenes)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Filtrar órdenes por estado
     */
    public function porEstado(string $estado)
    {
        try {
            $ordenes = $this->obtenerService->obtenerPorEstado($estado);

            return response()->json([
                'success' => true,
                'data' => $ordenes->map(fn($o) => $this->serializarOrden($o)),
                'count' => count($ordenes)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva orden en bodega (DDD)
     */
    public function store(Request $request)
    {
        try {
            $validado = $request->validate([
                'pedido' => 'required|integer|min:1',
                'cliente' => 'required|string|max:255',
                'fecha_creacion' => 'required|date',
                'estado' => 'nullable|string',
                'encargado' => 'nullable|string|max:255',
                'forma_pago' => 'nullable|string|max:255',
                'prendas' => 'nullable|array',
            ]);

            $orden = $this->crearService->ejecutar($validado);

            return response()->json([
                'success' => true,
                'message' => 'Orden creada exitosamente',
                'data' => $orden->toArray()
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear orden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado a "En Ejecución"
     */
    public function iniciarProduccion(int $numero)
    {
        try {
            $this->actualizarEstadoService->ejecutar($numero, 'En Ejecución');

            return response()->json([
                'success' => true,
                'message' => 'Orden en producción'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar estado a "Entregado"
     */
    public function completar(int $numero)
    {
        try {
            $this->actualizarEstadoService->ejecutar($numero, 'Entregado');

            return response()->json([
                'success' => true,
                'message' => 'Orden completada'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar una orden
     */
    public function destroy(int $numero)
    {
        try {
            $this->cancelarService->ejecutar($numero);

            return response()->json([
                'success' => true,
                'message' => 'Orden cancelada'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Serializar Orden Aggregate para HTTP
     */
    private function serializarOrden($orden): array
    {
        if (is_array($orden)) {
            return $orden;
        }

        return $orden->toArray();
    }
}
