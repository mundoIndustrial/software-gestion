<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Services\Pedidos\CotizacionSearchService;
use App\Services\Pedidos\CotizacionDataExtractorService;
use App\Services\Pedidos\PedidoProduccionCreatorService;
use App\Services\Pedidos\PrendaProcessorService;
use App\DTOs\CrearPedidoProduccionDTO;
use App\DTOs\CotizacionSearchDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Controller para Pedidos de Producción - Refactorizado con SOLID
 * 
 * Responsabilidades:
 * - SRP: Solo coordina requests HTTP y respuestas
 * - DIP: Inyecta Services, no accede directamente a modelos
 * - OCP: Fácil extender con nuevas funcionalidades
 */
class PedidoProduccionController extends Controller
{
    public function __construct(
        private CotizacionSearchService $cotizacionSearch,
        private CotizacionDataExtractorService $dataExtractor,
        private PedidoProduccionCreatorService $pedidoCreator,
        private PrendaProcessorService $prendaProcessor,
    ) {}

    /**
     * Listar todos los pedidos de producción del asesor
     * 
     * @return View
     */
    public function index(): View
    {
        $pedidos = PedidoProduccion::where('asesor_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('asesores.pedidos.index', [
            'pedidos' => $pedidos,
        ]);
    }

    /**
     * Muestra formulario para crear pedido desde cotización
     * 
     * @return View
     */
    public function mostrarFormularioCrearDesdeCotzacion(): View
    {
        // Obtener todas las cotizaciones
        $todas = $this->cotizacionSearch->obtenerTodas();

        // Convertir a DTOs para pasarlas a JavaScript
        $cotizacionesDTOs = $todas
            ->map(fn($cot) => $cot->toArray())
            ->values();

        return view('asesores.pedidos.crear-desde-cotizacion-refactorizado', [
            'cotizacionesDTOs' => $cotizacionesDTOs,
        ]);
    }

    /**
     * Crea un nuevo pedido de producción
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function crearDesdeCotzacion(Request $request): JsonResponse
    {
        try {
            // Validar request
            $validated = $request->validate([
                'cotizacion_id' => 'required|integer|exists:cotizaciones,id',
                'forma_de_pago' => 'nullable|string',
            ]);

            \Log::info('🔍 [PedidoProduccionController] Request recibido', [
                'validated' => $validated,
                'all_request' => $request->all(),
            ]);

            // Obtener cotización
            $cotizacion = Cotizacion::with('cliente')->findOrFail($validated['cotizacion_id']);

            \Log::info('🔍 [PedidoProduccionController] Cotización obtenida', [
                'cotizacion_id' => $cotizacion->id,
                'especificaciones' => $cotizacion->especificaciones,
            ]);

            // Extraer TODA la información normalizada de la cotización
            // (prendas, variantes, telas, tallas, fotos)
            $datosExtraidos = $this->dataExtractor->extraerDatos($cotizacion);

            // Extraer forma de pago de especificaciones
            $formaDePago = null;
            if ($cotizacion->especificaciones) {
                $especificaciones = is_array($cotizacion->especificaciones) 
                    ? $cotizacion->especificaciones 
                    : json_decode($cotizacion->especificaciones, true);
                
                \Log::info('🔍 [PedidoProduccionController] Especificaciones decodificadas', [
                    'especificaciones' => $especificaciones,
                ]);

                if (isset($especificaciones['forma_pago'])) {
                    $formaPagoArray = $especificaciones['forma_pago'];
                    \Log::info('🔍 [PedidoProduccionController] forma_pago encontrada en especificaciones', [
                        'forma_pago_array' => $formaPagoArray,
                        'es_array' => is_array($formaPagoArray),
                    ]);
                    
                    // Si es un array, tomar el primer elemento (si existe)
                    if (is_array($formaPagoArray) && !empty($formaPagoArray)) {
                        $formaDePago = $formaPagoArray[0];
                    } elseif (!is_array($formaPagoArray)) {
                        $formaDePago = $formaPagoArray;
                    }
                }
            }

            // Usar forma_de_pago del request si viene
            if (!empty($validated['forma_de_pago'])) {
                \Log::info('🔍 [PedidoProduccionController] Usando forma_de_pago del request', [
                    'forma_de_pago_request' => $validated['forma_de_pago'],
                ]);
                $formaDePago = $validated['forma_de_pago'];
            }

            \Log::info('🔍 [PedidoProduccionController] Forma de pago final antes de DTO', [
                'forma_de_pago_final' => $formaDePago,
            ]);

            // Crear DTO con los datos extraídos
            $dto = CrearPedidoProduccionDTO::fromRequest([
                'cotizacion_id' => $validated['cotizacion_id'],
                'prendas' => $datosExtraidos['prendas'],
                'cliente' => $datosExtraidos['cliente'],
                'cliente_id' => $datosExtraidos['cliente_id'],
                'forma_de_pago' => $formaDePago,
            ]);

            \Log::info('🔍 [PedidoProduccionController] DTO creado', [
                'dto_forma_de_pago' => $dto->formaDePago,
            ]);

            // Validar DTO
            if (!$dto->esValido()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos: No hay prendas con cantidades válidas'
                ], 422);
            }

            // Crear pedido usando Service
            $pedido = $this->pedidoCreator->crear(
                $dto,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'pedido_id' => $pedido->id,
                'redirect' => route('asesores.pedidos-produccion.index')
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene próximo número de pedido
     * 
     * @return JsonResponse
     */
    public function obtenerProximoNumero(): JsonResponse
    {
        return response()->json([
            'siguiente_pedido' => $this->pedidoCreator->obtenerProximoNumero()
        ]);
    }

    /**
     * Obtiene datos completos de una cotización
     * Utilizado por AJAX desde JavaScript
     * 
     * @param int $cotizacionId
     * @return JsonResponse
     */
    public function obtenerDatosCotizacion(int $cotizacionId): JsonResponse
    {
        try {
            $cotizacion = $this->cotizacionSearch->obtenerPorId($cotizacionId);

            if (!$cotizacion) {
                return response()->json([
                    'error' => 'Cotización no encontrada'
                ], 404);
            }

            return response()->json([
                'id' => $cotizacion->id,
                'numero' => $cotizacion->numero_cotizacion,
                'cliente' => $cotizacion->cliente,
                'asesora' => $cotizacion->asesora,
                'forma_pago' => $cotizacion->forma_pago ?? '',
                'prendas' => $cotizacion->prendasCotizaciones->map(function($prenda) {
                    return [
                        'nombre_producto' => $prenda->nombre_producto,
                        'descripcion' => $prenda->descripcion,
                        'tallas' => $prenda->tallas ?? [],
                        'fotos' => $prenda->fotos ?? [],
                        'variantes' => $prenda->variantes ?? [],
                    ];
                })->toArray(),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
