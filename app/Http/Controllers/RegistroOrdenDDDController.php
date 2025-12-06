<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Domain\Ordenes\Services\CrearOrdenService;
use App\Domain\Ordenes\Services\ActualizarEstadoOrdenService;
use App\Domain\Ordenes\Services\CancelarOrdenService;
use App\Domain\Ordenes\Services\ObtenerOrdenService;
use App\Domain\Ordenes\ValueObjects\NumeroOrden;
use App\Domain\Ordenes\ValueObjects\EstadoOrden;
use App\Domain\Ordenes\ValueObjects\FormaPago;
use App\Domain\Ordenes\ValueObjects\Area;
use App\Http\Requests\CrearOrdenRequest;
use App\Http\Requests\ActualizarOrdenRequest;

/**
 * Controller: RegistroOrdenDDDController
 * 
 * Version refactorizada con DDD.
 * Solo coordina entre HTTP y Application Services.
 * Sin lógica de negocio, sin DB directo.
 */
class RegistroOrdenDDDController extends Controller
{
    public function __construct(
        private CrearOrdenService $crearOrdenService,
        private ActualizarEstadoOrdenService $actualizarEstadoService,
        private CancelarOrdenService $cancelarOrdenService,
        private ObtenerOrdenService $obtenerOrdenService,
    ) {}

    /**
     * GET /api/ordenes
     * Listar todas las órdenes
     */
    public function index(): JsonResponse
    {
        try {
            $ordenes = $this->obtenerOrdenService->todas();

            return response()->json([
                'success' => true,
                'data' => $ordenes->map(fn($orden) => $this->serializarOrden($orden))->values(),
                'count' => $ordenes->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/ordenes/{numero}
     * Obtener orden específica
     */
    public function show(int $numero): JsonResponse
    {
        try {
            $orden = $this->obtenerOrdenService->porNumero($numero);

            return response()->json([
                'success' => true,
                'data' => $this->serializarOrden($orden),
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/ordenes/cliente/{cliente}
     * Obtener órdenes por cliente
     */
    public function porCliente(string $cliente): JsonResponse
    {
        try {
            $ordenes = $this->obtenerOrdenService->porCliente($cliente);

            return response()->json([
                'success' => true,
                'data' => $ordenes->map(fn($orden) => $this->serializarOrden($orden))->values(),
                'count' => $ordenes->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/ordenes/estado/{estado}
     * Obtener órdenes por estado
     */
    public function porEstado(string $estado): JsonResponse
    {
        try {
            $ordenes = $this->obtenerOrdenService->porEstado($estado);

            return response()->json([
                'success' => true,
                'data' => $ordenes->map(fn($orden) => $this->serializarOrden($orden))->values(),
                'count' => $ordenes->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/ordenes
     * Crear nueva orden
     */
    public function store(CrearOrdenRequest $request): JsonResponse
    {
        try {
            $numeroPedido = $this->crearOrdenService->ejecutar($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Orden {$numeroPedido} creada exitosamente",
                'data' => ['numero_pedido' => $numeroPedido],
            ], 201);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH /api/ordenes/{numero}/aprobar
     * Aprobar orden
     */
    public function aprobar(int $numero): JsonResponse
    {
        try {
            $this->actualizarEstadoService->aprobar($numero);

            return response()->json([
                'success' => true,
                'message' => "Orden {$numero} aprobada",
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH /api/ordenes/{numero}/iniciar-produccion
     * Iniciar producción
     */
    public function iniciarProduccion(int $numero): JsonResponse
    {
        try {
            $this->actualizarEstadoService->iniciarProduccion($numero);

            return response()->json([
                'success' => true,
                'message' => "Orden {$numero} en producción",
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH /api/ordenes/{numero}/completar
     * Completar orden
     */
    public function completar(int $numero): JsonResponse
    {
        try {
            $this->actualizarEstadoService->completar($numero);

            return response()->json([
                'success' => true,
                'message' => "Orden {$numero} completada",
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/ordenes/{numero}
     * Cancelar orden
     */
    public function destroy(int $numero): JsonResponse
    {
        try {
            $this->cancelarOrdenService->ejecutar($numero);

            return response()->json([
                'success' => true,
                'message' => "Orden {$numero} cancelada",
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Serializar una orden para respuesta JSON
     */
    private function serializarOrden($orden): array
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
