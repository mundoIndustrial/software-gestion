<?php

namespace App\Http\Controllers\Api_temp\V1;

use App\Http\Controllers\Controller;
use App\Domain\Ordenes\Services\CrearOrdenService;
use App\Domain\Ordenes\Services\ActualizarEstadoOrdenService;
use App\Domain\Ordenes\Services\CancelarOrdenService;
use App\Domain\Ordenes\Services\ObtenerOrdenService;
use Illuminate\Http\Request;

/**
 * OrdenController - API DDD Layer (FASE 3)
 * 
 * Responsabilidad única: Exponer Application Services de DDD vía HTTP
 * Cumple: SRP, DIP, OCP
 * 
 * Endpoints:
 * - GET    /api/v1/ordenes              (indexDDD)
 * - GET    /api/v1/ordenes/{numero}     (showDDD)
 * - POST   /api/v1/ordenes              (storeDDD)
 * - PATCH  /api/v1/ordenes/{numero}/aprobar        (aprobarDDD)
 * - PATCH  /api/v1/ordenes/{numero}/iniciar-produccion (iniciarProduccionDDD)
 * - PATCH  /api/v1/ordenes/{numero}/completar (completarDDD)
 * - DELETE /api/v1/ordenes/{numero}     (destroyDDD)
 * - GET    /api/v1/ordenes/cliente/{cliente} (porClienteDDD)
 * - GET    /api/v1/ordenes/estado/{estado} (porEstadoDDD)
 */
class OrdenController extends Controller
{
    protected $crearOrdenService;
    protected $actualizarEstadoService;
    protected $cancelarOrdenService;
    protected $obtenerOrdenService;

    public function __construct(
        CrearOrdenService $crearOrdenService,
        ActualizarEstadoOrdenService $actualizarEstadoService,
        CancelarOrdenService $cancelarOrdenService,
        ObtenerOrdenService $obtenerOrdenService
    )
    {
        $this->crearOrdenService = $crearOrdenService;
        $this->actualizarEstadoService = $actualizarEstadoService;
        $this->cancelarOrdenService = $cancelarOrdenService;
        $this->obtenerOrdenService = $obtenerOrdenService;
    }

    /**
     * Obtener todas las órdenes (mediante Domain Model)
     * GET /api/v1/ordenes
     */
    public function index()
    {
        try {
            $ordenes = $this->obtenerOrdenService->todas();

            return response()->json([
                'success' => true,
                'data' => $ordenes->map(fn($orden) => $this->serializar($orden))->values(),
                'count' => $ordenes->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener orden específica (mediante Domain Model)
     * GET /api/v1/ordenes/{numero}
     */
    public function show(int $numero)
    {
        try {
            $orden = $this->obtenerOrdenService->porNumero($numero);

            return response()->json([
                'success' => true,
                'data' => $this->serializar($orden),
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener órdenes por cliente
     * GET /api/v1/ordenes/cliente/{cliente}
     */
    public function porCliente(string $cliente)
    {
        try {
            $ordenes = $this->obtenerOrdenService->porCliente($cliente);

            return response()->json([
                'success' => true,
                'data' => $ordenes->map(fn($orden) => $this->serializar($orden))->values(),
                'count' => $ordenes->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener órdenes por estado
     * GET /api/v1/ordenes/estado/{estado}
     */
    public function porEstado(string $estado)
    {
        try {
            $ordenes = $this->obtenerOrdenService->porEstado($estado);

            return response()->json([
                'success' => true,
                'data' => $ordenes->map(fn($orden) => $this->serializar($orden))->values(),
                'count' => $ordenes->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Crear nueva orden
     * POST /api/v1/ordenes
     */
    public function store(Request $request)
    {
        try {
            $numeroPedido = $this->crearOrdenService->ejecutar($request->all());

            return response()->json([
                'success' => true,
                'message' => "Orden {$numeroPedido} creada exitosamente",
                'data' => ['numero_pedido' => $numeroPedido],
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Aprobar orden
     * PATCH /api/v1/ordenes/{numero}/aprobar
     */
    public function aprobar(int $numero)
    {
        try {
            $this->actualizarEstadoService->aprobar($numero);

            return response()->json([
                'success' => true,
                'message' => "Orden {$numero} aprobada",
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Iniciar producción
     * PATCH /api/v1/ordenes/{numero}/iniciar-produccion
     */
    public function iniciarProduccion(int $numero)
    {
        try {
            $this->actualizarEstadoService->iniciarProduccion($numero);

            return response()->json([
                'success' => true,
                'message' => "Orden {$numero} en producción",
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Completar orden
     * PATCH /api/v1/ordenes/{numero}/completar
     */
    public function completar(int $numero)
    {
        try {
            $this->actualizarEstadoService->completar($numero);

            return response()->json([
                'success' => true,
                'message' => "Orden {$numero} completada",
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancelar orden
     * DELETE /api/v1/ordenes/{numero}
     */
    public function destroy(int $numero)
    {
        try {
            $this->cancelarOrdenService->ejecutar($numero);

            return response()->json([
                'success' => true,
                'message' => "Orden {$numero} cancelada",
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Serializar una orden para respuesta JSON
     */
    private function serializar($orden): array
    {
        return [
            'numero_pedido' => $orden->getNumeroPedido()->toInt(),
            'cliente' => $orden->getCliente(),
            'estado' => $orden->getEstado()->toString(),
            'forma_pago' => $orden->getFormaPago()->toString(),
            'area' => $orden->getArea()->toString(),
            'fecha_creacion' => $orden->getFechaCreacion()->toIso8601String(),
            'fecha_ultima_modificacion' => $orden->getFechaUltimaModificacion()->toIso8601String(),
            'total_cantidad' => $orden->getTotalCantidad(),
            'total_entregado' => $orden->getTotalEntregado(),
            'total_pendiente' => $orden->getTotalPendiente(),
            'porcentaje_completado' => $orden->getPorcentajeCompletado(),
            'prendas' => $orden->getPrendas()->map(fn($prenda) => [
                'nombre' => $prenda->getNombrePrenda(),
                'cantidad_total' => $prenda->getCantidadTotal(),
                'cantidad_entregada' => $prenda->getCantidadEntregada(),
                'cantidad_pendiente' => $prenda->getCantidadPendiente(),
                'porcentaje_entrega' => $prenda->getPorcentajeEntrega(),
                'descripcion' => $prenda->getDescripcion(),
                'tallas' => $prenda->getCantidadTalla(),
            ])->values()->toArray(),
        ];
    }
}
