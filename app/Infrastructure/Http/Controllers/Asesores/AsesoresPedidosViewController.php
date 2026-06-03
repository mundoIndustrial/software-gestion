<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Asesores\UseCases\ListarBorradoresAsesorUseCase;
use App\Application\Asesores\UseCases\ObtenerCotizacionEditableAsesorUseCase;
use App\Application\Asesores\UseCases\ObtenerDatosCotizacionEditarUseCase;
use App\Application\Bodega\Services\BodegaPedidoService;
use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\PrepararCreacionProduccionPedidoDTO;
use App\Application\Pedidos\Presenters\PedidoTableRowPresenter;
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\PrepararCreacionProduccionPedidoUseCase;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Models\ConsecutivoReciboPedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

final class AsesoresPedidosViewController extends Controller
{
    public function __construct(
        private readonly PedidoProduccionReadRepository $pedidoProduccionRepository,
        private readonly ListarProduccionPedidosUseCase $listarProduccionPedidosUseCase,
        private readonly BodegaPedidoService $bodegaPedidoService,
        private readonly PrepararCreacionProduccionPedidoUseCase $prepararCreacionProduccionPedidoUseCase,
        private readonly ObtenerCotizacionEditableAsesorUseCase $obtenerCotizacionEditableAsesorUseCase,
        private readonly ObtenerDatosCotizacionEditarUseCase $obtenerDatosCotizacionEditarUseCase,
        private readonly ObtenerProduccionPedidoUseCase $obtenerProduccionPedidoUseCase,
        private readonly ListarBorradoresAsesorUseCase $listarBorradoresAsesorUseCase
    ) {
    }

    public function index(Request $request)
    {
        try {
            $filtros = [];
            $filtros['page'] = max(1, (int) $request->query('page', 1));
            $filtros['per_page'] = max(1, (int) $request->query('per_page', 15));
            if ($request->filled('estado')) {
                $filtros['estado'] = $request->estado;
            }
            if ($request->filled('search')) {
                $filtros['search'] = $request->search;
            }

            $user = Auth::user();
            $dto = ListarProduccionPedidosDTO::fromRequest(
                $request->query('tipo'),
                $filtros,
                $user?->id,
                (bool) ($user?->hasRole('asesor'))
            );

            $pedidos = $this->listarProduccionPedidosUseCase->ejecutar($dto);
            $pedidos->setCollection(
                $pedidos->getCollection()->map(
                    static fn($pedido) => PedidoTableRowPresenter::present($pedido)
                )
            );
            $estados = $request->query('tipo') !== 'logo' ? $this->listarProduccionPedidosUseCase->obtenerEstados() : [];

            return view('asesores.pedidos.index', compact('pedidos', 'estados'));
        } catch (\Throwable $e) {
            \Log::error('Error al listar pedidos', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error al listar pedidos');
        }
    }

    public function pendientes(Request $request)
    {
        try {
            $user = Auth::user();
            return view('asesores.pedidos.pendientes', [
                'search' => $request->query('search', ''),
                'tipo' => $request->query('tipo', 'todos'),
                'userName' => $user->name ?? 'Usuario',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al mostrar pendientes', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error al cargar pedidos pendientes');
        }
    }

    public function pendientesDetalle($id)
    {
        try {
            $pedidoData = $this->pedidoProduccionRepository->obtenerPedidoPorId((int) $id);
            if ($pedidoData === null) {
                abort(404, 'Pedido no encontrado');
            }

            $datosCompletos = $this->bodegaPedidoService->obtenerDetallePedido($id);
            $itemsPendientes = collect($datosCompletos['items'])->filter(function ($item) {
                return ($item['estado_bodega'] ?? '') === 'Pendiente';
            })->values()->all();

            return view('asesores.pedidos.pendientes-detalle', [
                'pedido' => (object) $pedidoData,
                'detalles' => $itemsPendientes,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al mostrar detalle de pendientes', ['error' => $e->getMessage()]);
            return redirect()->route('asesores.pendientes')->with('error', 'Error al cargar el detalle');
        }
    }

    public function pendientesLogo(Request $request)
    {
        try {
            $user = Auth::user();
            return view('asesores.pedidos.pendientes-logo', [
                'userName' => $user->name ?? 'Usuario',
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error al mostrar pendientes logo', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error al cargar diseños pendientes');
        }
    }

    public function create(Request $request)
    {
        try {
            if ($request->query('tipo') === 'PARA_CLIENTE') {
                $tipo = 'PARA_CLIENTE';
                return view('asesores.cotizaciones.epp.create', compact('tipo'));
            }

            $dto = PrepararCreacionProduccionPedidoDTO::fromRequest(
                tipo: $request->query('tipo', 'PB'),
                editarId: $request->query('editar'),
                usuarioId: Auth::id(),
                allowEditarCotizacionCreada: $request->boolean('editar_cotizacion')
            );

            $datos = $this->prepararCreacionProduccionPedidoUseCase->ejecutar($dto);
            $tipo = $datos['tipo'];
            $esEdicion = $datos['esEdicion'];
            $cotizacion = $datos['cotizacion'];

            if ($tipo === 'B') {
                return redirect()->route('asesores.cotizaciones-bordado.create');
            }
            if ($tipo === 'PL') {
                return redirect()->route('asesores.cotizaciones-prenda.create');
            }

            return view('asesores.pedidos.create-friendly', compact('tipo', 'esEdicion', 'cotizacion'));
        } catch (\Throwable $e) {
            \Log::error('Error al preparar formulario de creación', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error al preparar formulario de creación.');
        }
    }

    public function editCotizacion(int $id, Request $request)
    {
        try {
            $asesorId = (int) (Auth::id() ?? 0);
            $cotizacionResumen = $this->obtenerCotizacionEditableAsesorUseCase->ejecutar($id, $asesorId);

            if ($request->query('tipo') !== 'PARA_CLIENTE') {
                $codigoTipo = $cotizacionResumen['tipo_codigo'] ?? null;
                if (is_string($codigoTipo) && strtoupper(trim($codigoTipo)) === 'EPP') {
                    return redirect("/asesores/cotizaciones/{$id}/edit?tipo=PARA_CLIENTE");
                }
                abort(404);
            }

            $datosCompletos = $this->obtenerDatosCotizacionEditarUseCase->ejecutar($id);

            $itemsUi = collect($datosCompletos['items_epp'] ?? [])->map(function ($item) {
                return array_merge($item, [
                    'tipo' => 'epp',
                    'nombre_epp' => $item['nombre'] ?? '',
                    'observaciones' => $item['observaciones'] ?? '',
                    'imagenes' => collect($item['imagenes'] ?? [])->map(fn($img) => \Storage::disk('public')->url($img['url']))->filter()->values()->all(),
                ]);
            })->values()->all();

            $prendasUi = collect($datosCompletos['items_prendas'] ?? [])->map(function ($prenda) {
                return array_merge($prenda, [
                    'tipo' => 'prenda',
                    'nombre_epp' => $prenda['nombre'] ?? '',
                    'observaciones' => $prenda['observaciones'] ?? '',
                    'imagenes' => collect($prenda['imagenes'] ?? [])->map(fn($img) => \Storage::disk('public')->url($img['url']))->filter()->values()->all(),
                ]);
            })->values()->all();

            $itemsUi = array_merge($itemsUi, $prendasUi);
            $eppCot = $datosCompletos['epp_cot'] ?? null;

            $cotizacion = (object) [
                'id' => $cotizacionResumen['id'],
                'tipo_venta' => $cotizacionResumen['tipo_venta'],
                'iva' => $cotizacionResumen['iva'],
                'cliente_nit' => $cotizacionResumen['cliente_nit'],
                'cliente_direccion' => $cotizacionResumen['cliente_direccion'],
                'cliente_telefono' => $cotizacionResumen['cliente_telefono'],
                'especificaciones' => $cotizacionResumen['especificaciones'],
                'cliente' => (object) ['nombre' => $cotizacionResumen['cliente_nombre']],
            ];

            $especificaciones = [];
            if (!empty($cotizacion->especificaciones)) {
                $decoded = json_decode((string) $cotizacion->especificaciones, true);
                if (is_array($decoded)) {
                    $especificaciones = $decoded;
                }
            }

            $iva = $cotizacion->iva ?? null;
            $condicionesPago = $especificaciones['condiciones_pago'] ?? '';
            $tiempoEntrega = $especificaciones['tiempo_entrega'] ?? '';
            $cuentasAutorizadas = $especificaciones['cuentas_autorizadas'] ?? '';
            $tipo = 'PARA_CLIENTE';

            return view('asesores.cotizaciones.epp.create', compact(
                'tipo',
                'cotizacion',
                'eppCot',
                'itemsUi',
                'iva',
                'condicionesPago',
                'tiempoEntrega',
                'cuentasAutorizadas'
            ));
        } catch (\Throwable $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                throw $e;
            }
            \Log::error('[editCotizacion] Error', ['error' => $e->getMessage()]);
            abort(500, 'Error al cargar la cotización.');
        }
    }

    public function show($id)
    {
        try {
            $dto = ObtenerProduccionPedidoDTO::fromRequest((string) $id);
            $pedidoData = $this->obtenerProduccionPedidoUseCase->ejecutar($dto);
            return view('asesores.pedidos.plantilla-erp-antigua', compact('pedidoData'));
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Error al cargar el pedido.');
        }
    }

    public function edit($id)
    {
        try {
            $dto = ObtenerProduccionPedidoDTO::fromRequest((string) $id);
            $respuesta = $this->obtenerProduccionPedidoUseCase->ejecutar($dto);

            $pedidoData = $this->pedidoProduccionRepository->obtenerPedidoPorId((int) $id);
            if ($pedidoData === null) {
                throw new \RuntimeException('Pedido no encontrado para edición');
            }

            return view('asesores.pedidos.editar-pedido', [
                'pedido' => (object) $pedidoData,
                'pedidoData' => $respuesta,
            ]);
        } catch (\Throwable $e) {
            \Log::error('[edit] Error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error al cargar la edición del pedido.');
        }
    }

    public function borradores(Request $request)
    {
        try {
            $user = Auth::user();
            $borradores = $this->listarBorradoresAsesorUseCase->ejecutar(
                (int) $user->id,
                max(1, (int) $request->query('page', 1)),
                15
            );

            return view('asesores.pedidos.borradores', [
                'borradores' => $borradores,
                'asesor' => $user,
            ]);
        } catch (\Throwable $e) {
            \Log::error('[borradores] Error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error al listar borradores.');
        }
    }

    public function revisarPrendas(Request $request)
    {
        try {
            $asesorId = (int) Auth::id();
            $search = trim((string) $request->query('search', ''));

            $query = ConsecutivoReciboPedido::query()
                ->with([
                    'pedido:id,numero_pedido,asesor_id',
                    'pedido.prendas:id,pedido_produccion_id,nombre_prenda',
                    'prenda:id,pedido_produccion_id,nombre_prenda',
                    'prenda.fotos:id,prenda_pedido_id,ruta_original,ruta_webp',
                    'prendaBodega:id,nombre,descripcion',
                ])
                ->where('activo', 1)
                ->whereRaw(
                    "UPPER(REPLACE(TRIM(COALESCE(estado, '')), ' ', '_')) IN (?, ?)",
                    ['DEVUELTO_ASESOR', 'DEVUELTO_A_ASESOR']
                )
                ->whereHas('pedido', static function ($pedidoQuery) use ($asesorId) {
                    $pedidoQuery->where('asesor_id', $asesorId);
                })
                ->orderByDesc('updated_at');

            if ($search !== '') {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->whereHas('pedido', static function ($pedidoQuery) use ($search) {
                            $pedidoQuery->where('numero_pedido', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('prenda', static function ($prendaQuery) use ($search) {
                            $prendaQuery->where('nombre_prenda', 'like', '%' . $search . '%');
                        })
                        ->orWhere('consecutivo_actual', 'like', '%' . $search . '%');
                });
            }

            $recibos = $query->paginate(20)->withQueryString();
            $recibos->getCollection()->transform(function (ConsecutivoReciboPedido $recibo) {
                $imagenesPrenda = $recibo->prenda?->fotos
                    ?->map(function ($foto) {
                        $ruta = (string) ($foto->ruta_webp ?: $foto->ruta_original ?: '');
                        if ($ruta === '') {
                            return null;
                        }

                        if (str_starts_with($ruta, '/storage/') || str_starts_with($ruta, 'http')) {
                            return $ruta;
                        }

                        if (str_starts_with($ruta, '/')) {
                            return '/storage' . $ruta;
                        }

                        return '/storage/' . $ruta;
                    })
                    ->filter()
                    ->values()
                    ->all() ?? [];

                $prendaRelacion = $recibo->prenda;
                $prendaFallback = $recibo->pedido?->prendas
                    ?->sortBy('id')
                    ->first();
                $prendaBodega = $recibo->prendaBodega;

                $prendaId = (int) ($recibo->prenda_id ?: ($prendaRelacion->id ?? 0) ?: ($prendaFallback->id ?? 0));
                $nombrePrenda = (string) (
                    $prendaRelacion->nombre_prenda
                    ?? $prendaFallback->nombre_prenda
                    ?? $prendaBodega->nombre
                    ?? $prendaBodega->descripcion
                    ?? 'PRENDA'
                );

                return [
                    'id' => $recibo->id,
                    'pedido_produccion_id' => (int) $recibo->pedido_produccion_id,
                    'prenda_id' => $prendaId,
                    'numero_pedido' => (string) ($recibo->pedido?->numero_pedido ?? ''),
                    'nombre_prenda' => $nombrePrenda,
                    'tipo_recibo' => (string) $recibo->tipo_recibo,
                    'tipo_recibo_normalizado' => strtoupper(trim((string) $recibo->tipo_recibo)),
                    'consecutivo_actual' => (int) ($recibo->consecutivo_actual ?? 0),
                    'estado' => $this->formatEstadoRecibo($recibo->estado),
                    'area' => (string) ($recibo->area ?? ''),
                    'notas' => (string) ($recibo->notas ?? ''),
                    'fecha_envio' => optional($recibo->fecha_envio)->format('Y-m-d H:i'),
                    'fecha_llegada' => optional($recibo->fecha_llegada)->format('Y-m-d H:i'),
                    'updated_at' => optional($recibo->updated_at)->format('Y-m-d H:i'),
                    'imagenes_prenda' => $imagenesPrenda,
                ];
            });

            return view('asesores.pedidos.revisar-prenda', [
                'recibos' => $recibos,
                'search' => $search,
            ]);
        } catch (\Throwable $e) {
            \Log::error('[revisarPrendas] Error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error al cargar prendas para revisión.');
        }
    }

    private function formatEstadoRecibo(?string $estado): string
    {
        $estadoNormalized = strtoupper(trim((string)$estado));

        if (in_array($estadoNormalized, ['DEVUELTO_ASESOR', 'DEVUELTO_A_ASESOR'], true)) {
            return 'DEVUELTO ASESOR';
        }

        return str_replace('_', ' ', $estadoNormalized);
    }

    public function aprobarReciboCosturaParaInsumos(Request $request, int $reciboId): JsonResponse
    {
        try {
            $asesorId = (int) Auth::id();

            /** @var ConsecutivoReciboPedido|null $recibo */
            $recibo = ConsecutivoReciboPedido::query()
                ->with(['pedido:id,asesor_id'])
                ->where('id', $reciboId)
                ->where('activo', 1)
                ->first();

            if (!$recibo || !$recibo->pedido) {
                return response()->json(['success' => false, 'message' => 'Recibo no encontrado.'], 404);
            }

            if ((int) $recibo->pedido->asesor_id !== $asesorId) {
                return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
            }

            $tipo = strtoupper(trim((string) $recibo->tipo_recibo));
            if (!in_array($tipo, ['COSTURA', 'COSTURA-BODEGA'], true)) {
                return response()->json(['success' => false, 'message' => 'Solo aplica para recibos de COSTURA o COSTURA-BODEGA.'], 422);
            }

            $estadoNormalizado = strtoupper(str_replace(' ', '_', trim((string) ($recibo->estado ?? ''))));
            if (!in_array($estadoNormalizado, ['DEVUELTO_ASESOR', 'DEVUELTO_A_ASESOR'], true)) {
                return response()->json(['success' => false, 'message' => 'El recibo no está en estado devuelto.'], 422);
            }

            $recibo->estado = ConsecutivoReciboPedido::ESTADO_PENDIENTE_INSUMOS;
            $recibo->save();

            return response()->json(['success' => true, 'message' => 'Recibo aprobado y enviado a Insumos.']);
        } catch (\Throwable $e) {
            \Log::error('[aprobarReciboCosturaParaInsumos] Error', [
                'reciboId' => $reciboId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['success' => false, 'message' => 'Error al aprobar el recibo.'], 500);
        }
    }
}
