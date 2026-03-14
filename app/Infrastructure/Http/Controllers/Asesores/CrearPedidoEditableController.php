<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Cotizacion;
use App\Domain\Pedidos\Services\PedidoWebService;
use App\Application\Services\ImageUploadService;
use App\Application\Services\ColorTelaService;
use App\Domain\Pedidos\Services\ResolutorImagenesService;
use App\Domain\Pedidos\Services\MapeoImagenesService;
use App\Domain\Pedidos\Services\ProcesoImagenService;
use App\Domain\Pedidos\Services\PedidoImagenesService;
use App\Application\UseCases\Pedidos\CrearPedidoCompleteUseCase;
use App\Application\UseCases\Pedidos\CrearPedidoInput;
use App\Application\UseCases\Pedidos\ValidarPedidoUseCase;
use App\Application\UseCases\Pedidos\ValidarPedidoInput;
use App\Application\UseCases\Pedidos\GuardarBorradorUseCase;
use App\Application\UseCases\Pedidos\GuardarBorradorInput;
use App\Application\UseCases\Pedidos\ActualizarBorradorUseCase;
use App\Application\UseCases\Pedidos\ActualizarBorradorInput;
use App\Domain\Clientes\Services\ClienteService;
use App\Domain\Epp\Repositories\EppRepository;
use App\Application\Services\Pedidos\MapearItemsEppCotizacionService;
use App\Application\Services\Pedidos\MapearPedidoEdicionService;
use App\Application\Services\Pedidos\Contracts\ObtenerItemsServiceInterface;
use App\Application\Services\Pedidos\Contracts\PrepararCrearPedidoServiceInterface;
use App\Application\Services\Pedidos\Contracts\CargarDatosCompartidosServiceInterface;
use App\Application\Services\TimerService;
use App\Domain\Pedidos\Constants\PedidoConstants;
use App\Domain\Prendas\Repositories\TipoPrendaRepository;

/**
 * Controller HTTP para creación de pedidos. Solo orquesta entrada/salida.
 */
class CrearPedidoEditableController extends Controller
{
    public function __construct(
        private PedidoWebService $pedidoWebService,
        private ImageUploadService $imageUploadService,
        private ColorTelaService $colorTelaService,
        private ResolutorImagenesService $resolutorImagenes,
        private MapeoImagenesService $mapeoImagenes,
        private ProcesoImagenService $procesoImagenService,
        private PedidoImagenesService $pedidoImagenesService,
        private CrearPedidoCompleteUseCase $crearPedidoUseCase,
        private ValidarPedidoUseCase $validarPedidoUseCase,
        private GuardarBorradorUseCase $guardarBorradorUseCase,
        private ActualizarBorradorUseCase $actualizarBorradorUseCase,
        private ClienteService $clienteService,
        private EppRepository $eppRepository,
        private MapearItemsEppCotizacionService $mapearItemsEpp,
        private MapearPedidoEdicionService $mapearPedidoEdicion,
        private ObtenerItemsServiceInterface $obtenerItemsService,
        private PrepararCrearPedidoServiceInterface $prepararCrearPedidoService,
        private CargarDatosCompartidosServiceInterface $cargarDatosCompartidosService,
        private TimerService $timerService,
        private TipoPrendaRepository $tipoPrendaRepository,
    ) {}

    /** GET /asesores/pedidos-editable/crear-desde-cotizacion */
    public function crearDesdeCotizacion(Request $request): View
    {
        $timerTotal = $this->timerService->iniciar('crearDesdeCotizacion-total');
        $user = Auth::user();
        $datosCompartidos = $this->cargarDatosCompartidosService->ejecutar($user);
        
        $timerCotizaciones = $this->timerService->iniciar('crearDesdeCotizacion-cotizaciones');
        $cotizaciones = Cotizacion::with([
            'cliente',
            'tipoCotizacion',
            'prendas' => function($query) {
                $query->with([
                    'fotos', 
                    'telaFotos', 
                    'tallas.genero',
                    'variantes',
                    'logoCotizacionTelasPrenda'
                ]);
            },
            'logoCotizacion.fotos',
            'logoCotizacion.telasPrendas'
        ])
            ->where('asesor_id', $user->id)
            ->whereIn('estado', PedidoConstants::COTIZACIONES_PARA_PEDIDO)
            ->orderBy('created_at', 'desc')
            ->get();
        $tiempoCotizaciones = $timerCotizaciones->obtenerMs();
        $tiempoTotalMs = $timerTotal->obtenerMs();
        Log::info('[CREAR-DESDE-COTIZACION] Completado', [
            'cotizaciones' => $cotizaciones->count(),
            'tiempo_ms' => $tiempoTotalMs,
        ]);
        
        return view('asesores.pedidos.crear-pedido-desde-cotizacion', [
            'cotizacionesData' => $cotizaciones,
            'pedidos' => $datosCompartidos['pedidos'],
            'clientes' => $datosCompartidos['clientes'],
            'tallas' => $datosCompartidos['tallas'],
            'tecnicas' => $datosCompartidos['tecnicas'],
            'formasPago' => $datosCompartidos['formas_pago'],
            'modoEdicion' => false
        ]);
    }

    /** GET /asesores/pedidos-editable/crear-nuevo */
    public function crearNuevo(Request $request): View
    {
        $timerTotal = $this->timerService->iniciar('crearNuevo-total');
        $user = Auth::user();
        $datosCompartidos = $this->cargarDatosCompartidosService->ejecutar($user);
        
        $editId = $request->query('edit') ? (int) $request->query('edit') : null;
        $datosEdicion = $this->prepararCrearPedidoService->ejecutar($editId);

        $tiempoTotalMs = $timerTotal->obtenerMs();
        Log::info('[CREAR-PEDIDO-NUEVO] Completado', [
            'tiempo_ms' => $tiempoTotalMs,
            'modo_edicion' => $datosEdicion['modo_edicion'],
        ]);
        
        return view('asesores.pedidos.crear-pedido-nuevo', [
            'cotizaciones' => collect([]),
            'pedidos' => $datosCompartidos['pedidos'],
            'clientes' => $datosCompartidos['clientes'],
            'tallas' => $datosCompartidos['tallas'],
            'tecnicas' => $datosCompartidos['tecnicas'],
            'formasPago' => $datosCompartidos['formas_pago'],
            'modoEdicion' => $datosEdicion['modo_edicion'],
            'pedidoEditarId' => $datosEdicion['pedido_editar_id'],
            'pedido' => $datosEdicion['pedido_editar'],
            'epps' => $datosEdicion['epps_editar'],
            'estados' => [],
            'areas' => [],
        ]);
    }

    /** Obtener items EPP de una cotización vía interfaz */
    public function obtenerItemsEppCotizacion(Request $request, Cotizacion $cotizacion): JsonResponse
    {
        try {
            if ((int) $cotizacion->asesor_id !== (int) Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado',
                ], 403);
            }

            $resultado = $this->obtenerItemsService->ejecutar($cotizacion);

            return response()->json([
                'success' => true,
                'cotizacion_id' => (int) $cotizacion->id,
                'items' => $resultado['items'],
            ]);
        } catch (\Exception $e) {
            Log::error('[obtenerItemsEppCotizacion] Error', [
                'cotizacion_id' => $cotizacion->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener items EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /** Validar pedido antes de crear (delega a ValidarPedidoUseCase) */
    public function validarPedido(Request $request): JsonResponse
    {
        try {
            $input = ValidarPedidoInput::fromRequest($request, Auth::id());
            $output = $this->validarPedidoUseCase->ejecutar($input);

            return response()->json(
                $output->toArray(),
                $output->success ? 200 : 422
            );

        } catch (\Exception $e) {
            Log::error('[CrearPedidoEditableController] Error en validarPedido', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /** POST /asesores/pedidos-editable/crear - Crear pedido transaccional */
    public function crearPedido(Request $request): JsonResponse
    {
        try {
            $input = CrearPedidoInput::fromRequest($request, Auth::id());
            $output = $this->crearPedidoUseCase->ejecutar($input);

            return response()->json(
                $output->toArray(),
                $output->success ? 200 : 500
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('[CrearPedidoEditableController] Error en crearPedido', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /** GET /asesores/api/prendas/autocomplete */
    public function obtenerPrendasAutocomplete(Request $request): JsonResponse
    {
        try {
            $busqueda = $request->input('q', '');
            $prendasDb = $this->tipoPrendaRepository->buscarActivas($busqueda, 50);
            
            $prendas = $prendasDb->map(function($prenda) {
                return [
                    'id' => $prenda->id,
                    'nombre' => $prenda->nombre,
                    'codigo' => $prenda->codigo,
                    'descripcion' => $prenda->descripcion
                ];
            });
            
            return response()->json([
                'success' => true,
                'prendas' => $prendas
            ]);
            
        } catch (\Exception $e) {
            Log::error('[obtenerPrendasAutocomplete] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener prendas: ' . $e->getMessage()
            ], 500);
        }
    }

    /** POST /asesores/pedidos-editable/borrador - Guardar como borrador */
    public function guardarBorrador(Request $request): JsonResponse
    {
        try {
            $input = GuardarBorradorInput::fromRequest($request, Auth::id());
            $output = $this->guardarBorradorUseCase->ejecutar($input);

            return response()->json(
                $output->toArray(),
                $output->success ? 200 : 500
            );

        } catch (\Exception $e) {
            Log::error('[CrearPedidoEditableController] Error en guardarBorrador', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /** POST /asesores/pedidos-editable/{pedido}/actualizar-borrador */
    public function actualizarBorrador($pedidoId, Request $request): JsonResponse
    {
        try {
            $input = ActualizarBorradorInput::fromRequest($request, (int) $pedidoId, Auth::id());
            $output = $this->actualizarBorradorUseCase->ejecutar($input);

            return response()->json(
                $output->toArray(),
                $output->success ? 200 : 500
            );

        } catch (\Exception $e) {
            Log::error('[CrearPedidoEditableController] Error en actualizarBorrador', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}