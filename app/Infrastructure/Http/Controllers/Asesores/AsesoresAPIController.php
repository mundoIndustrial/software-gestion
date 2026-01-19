<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Application\Services\Asesores\CrearPedidoService;
use App\Application\Services\Asesores\ObtenerFotosService;
use App\Application\Services\Asesores\AnularPedidoService;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;

/**
 * AsesoresAPIController
 * 
 * Controlador DDD para APIs de asesores.
 * Delega toda la lógica de negocio a servicios de aplicación.
 * 
 * Responsabilidades:
 * - Validar entrada HTTP
 * - Llamar a servicios de aplicación
 * - Formatear respuesta HTTP
 */
class AsesoresAPIController extends Controller
{
    protected CrearPedidoService $crearPedidoService;
    protected ObtenerFotosService $obtenerFotosService;
    protected AnularPedidoService $anularPedidoService;
    protected PedidoProduccionRepository $pedidoProduccionRepository;

    public function __construct(
        CrearPedidoService $crearPedidoService,
        ObtenerFotosService $obtenerFotosService,
        AnularPedidoService $anularPedidoService,
        PedidoProduccionRepository $pedidoProduccionRepository
    ) {
        $this->crearPedidoService = $crearPedidoService;
        $this->obtenerFotosService = $obtenerFotosService;
        $this->anularPedidoService = $anularPedidoService;
        $this->pedidoProduccionRepository = $pedidoProduccionRepository;
        $this->middleware('auth');
    }

    /**
     * POST /asesores/pedidos
     * Crear nuevo pedido (prendas o logo)
     */
    public function store(Request $request)
    {
        $productosKey = $request->has('productos') ? 'productos' : 'productos_friendly';

        $validated = $request->validate([
            'cliente' => 'required|string|max:255',
            'forma_de_pago' => 'nullable|string|max:69',
            'area' => 'nullable|string',
            $productosKey => 'required|array|min:1',
            $productosKey.'.*.nombre_producto' => 'required|string',
            $productosKey.'.*.descripcion' => 'nullable|string',
            $productosKey.'.*.tella' => 'nullable|string',
            $productosKey.'.*.tipo_manga' => 'nullable|string',
            $productosKey.'.*.color' => 'nullable|string',
            $productosKey.'.*.talla' => 'nullable|string',
            $productosKey.'.*.genero' => 'nullable|string',
            $productosKey.'.*.cantidad' => 'required|integer|min:1',
            $productosKey.'.*.ref_hilo' => 'nullable|string',
            $productosKey.'.*.precio_unitario' => 'nullable|numeric|min:0',
            $productosKey.'.*.telas' => 'nullable|array',
            $productosKey.'.*.telas.*.tela_id' => 'nullable|integer',
            $productosKey.'.*.telas.*.color_id' => 'nullable|integer',
            $productosKey.'.*.telas.*.referencia' => 'nullable|string',
            'logo.descripcion' => 'nullable|string',
            'logo.observaciones_tecnicas' => 'nullable|string',
            'logo.tecnicas' => 'nullable|string',
            'logo.ubicaciones' => 'nullable|string',
            'logo.observaciones_generales' => 'nullable|string',
            'logo.imagenes' => 'nullable|array',
            'logo.imagenes.*' => 'nullable|file|image|max:5242880',
            'tipo_cotizacion' => 'nullable|string',
            'cotizacion_id' => 'nullable|integer',
        ]);

        try {
            $datosParaCrear = [
                'cliente' => $validated['cliente'],
                'forma_de_pago' => $validated['forma_de_pago'] ?? null,
                'area' => $validated['area'] ?? null,
                $productosKey => $validated[$productosKey],
                'logo' => [
                    'descripcion' => $validated['logo.descripcion'] ?? null,
                    'observaciones_tecnicas' => $validated['logo.observaciones_tecnicas'] ?? null,
                    'tecnicas' => $validated['logo.tecnicas'] ?? null,
                    'ubicaciones' => $validated['logo.ubicaciones'] ?? null,
                    'observaciones_generales' => json_decode($validated['logo.observaciones_generales'] ?? '[]', true),
                    'imagenes' => $request->file('logo.imagenes') ?? [],
                ],
                'cotizacion_id' => $validated['cotizacion_id'] ?? null,
                'archivos' => $request->allFiles(),
            ];

            $resultado = $this->crearPedidoService->crear($datosParaCrear, $validated['tipo_cotizacion'] ?? null);

            if (is_int($resultado)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pedido de logo guardado correctamente',
                    'logo_pedido_id' => $resultado,
                    'tipo' => 'logo'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pedido guardado como borrador',
                'borrador_id' => $resultado->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Error creando pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/pedidos/confirm
     * Confirmar pedido y asignar número
     */
    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'borrador_id' => 'required|integer|exists:pedidos_produccion,id',
            'numero_pedido' => 'required|integer|unique:pedidos_produccion,numero_pedido',
        ]);

        try {
            $pedido = PedidoProduccion::findOrFail($validated['borrador_id']);
            $pedido->update(['numero_pedido' => $validated['numero_pedido']]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente con ID: ' . $validated['numero_pedido'],
                'pedido' => $validated['numero_pedido']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/pedidos/{id}/recibos-datos
     * Obtener datos de recibos dinámicos
     */
    public function obtenerDatosRecibos($id)
    {
        try {
            $pedidoId = $id;
            \Log::info('[RECIBOS] Obteniendo datos de recibos para pedido: ' . $pedidoId);

            $pedido = PedidoProduccion::find($pedidoId);

            if (!$pedido) {
                return response()->json(['error' => 'Pedido no encontrado'], 404);
            }

            if ($pedido->asesor_id && $pedido->asesor_id !== Auth::id()) {
                return response()->json(['error' => 'No tienes permiso para ver este pedido'], 403);
            }

            $datos = $this->pedidoProduccionRepository->obtenerDatosRecibos($pedidoId);

            \Log::info('[RECIBOS] Datos obtenidos correctamente', [
                'pedido_id' => $pedidoId,
                'prendas' => count($datos['prendas']),
            ]);

            return response()->json($datos);

        } catch (\Exception $e) {
            \Log::error('[RECIBOS] Error obteniendo datos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Error obteniendo datos de los recibos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /asesores/prendas-pedido/{prendaPedidoId}/fotos
     * Obtener fotos de una prenda de pedido
     */
    public function obtenerFotosPrendaPedido($prendaPedidoId)
    {
        try {
            $resultado = $this->obtenerFotosService->obtenerFotosPrendaPedido($prendaPedidoId);
            return response()->json($resultado);

        } catch (\Exception $e) {
            \Log::error('[FOTOS] Error obteniendo fotos', [
                'prenda_pedido_id' => $prendaPedidoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo fotos: ' . $e->getMessage(),
                'fotos' => [],
            ], $this->getHttpStatusCode($e));
        }
    }

    /**
     * POST /asesores/pedidos/{id}/anular
     * Anular un pedido
     */
    public function anularPedido(Request $request, $id)
    {
        $request->validate([
            'novedad' => 'required|string|min:10|max:500',
        ], [
            'novedad.required' => 'La novedad es obligatoria',
            'novedad.min' => 'La novedad debe tener al menos 10 caracteres',
            'novedad.max' => 'La novedad no puede exceder 500 caracteres',
        ]);

        try {
            $pedido = $this->anularPedidoService->anular($id, $request->novedad);

            return response()->json([
                'success' => true,
                'message' => 'Pedido anulado correctamente',
                'pedido' => $pedido,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error anulando pedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $this->getHttpStatusCode($e));
        }
    }

    /**
     * Obtener código HTTP de excepción
     */
    protected function getHttpStatusCode(\Exception $e): int
    {
        if (str_contains($e->getMessage(), 'No tienes permiso')) {
            return 403;
        }
        if (str_contains($e->getMessage(), 'no encontrado')) {
            return 404;
        }
        return 500;
    }
}
