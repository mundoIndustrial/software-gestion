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
}
