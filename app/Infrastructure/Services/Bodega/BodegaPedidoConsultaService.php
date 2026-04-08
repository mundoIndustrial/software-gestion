<?php

namespace App\Infrastructure\Services\Bodega;

use App\Application\Bodega\Calculators\PedidoEstadoCalculator;
use App\Application\Bodega\Constants\WarehouseConstants;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Models\BodegaDetallesTalla;
use App\Models\PedidoProduccion;
use App\Models\PedidoRevisado;
use App\Models\PedidoVistoSupervisor;
use App\Models\ReciboPrenda;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BodegaPedidoConsultaService
{
    public function __construct(
        private BodegaRoleService $roleService,
        private BodegaRepository $bodegaRepository,
        private PedidoEstadoCalculator $estadoCalculator,
        private PedidoProduccionReadRepository $pedidoRepository,
        private BodegaPedidoDetalleService $detalleService,
        private BodegaPedidoHistorialService $historialService
    ) {
    }

    public function obtenerPedidosPaginados(Request $request): array
    {
        $usuario = auth()->user();
        $rolesDelUsuario = $usuario->getRoleNames()->toArray();

        $areasPermitidas = $this->roleService->obtenerAreasPermitidas($rolesDelUsuario);
        $estadosPermitidos = $this->obtenerEstadosPermitidos();

        $todosLosPedidos = $this->bodegaRepository->obtenerPedidosBase($estadosPermitidos);
        $todosLosPedidos = $this->filtrarPedidosAnulados($todosLosPedidos);

        $pedidosFiltradosPorRol = $this->filtrarPedidosPorArea($todosLosPedidos, $areasPermitidas);
        $paginacion = $this->paginarPedidos($pedidosFiltradosPorRol, $request);

        if ($request->query('view') === WarehouseConstants::VIEW_DETAILS) {
            return $this->procesarVistaDetallada($paginacion, $rolesDelUsuario, $areasPermitidas);
        }

        return $this->procesarVistaLista($paginacion, $pedidosFiltradosPorRol);
    }

    public function obtenerPedidosAnuladosPaginados(Request $request): array
    {
        $usuario = auth()->user();
        $rolesDelUsuario = $usuario->getRoleNames()->toArray();

        $areasPermitidas = $this->roleService->obtenerAreasPermitidas($rolesDelUsuario);

        $numerosAnulados = PedidoProduccion::query()
            ->where('estado', WarehouseConstants::STATE_CANCELLED)
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->pluck('numero_pedido')
            ->filter(fn ($n) => !empty($n))
            ->unique()
            ->values();

        $todosLosPedidos = ReciboPrenda::with(['asesor'])
            ->whereIn('numero_pedido', $numerosAnulados)
            ->orderBy('updated_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $pedidosFiltradosPorRol = $this->filtrarPedidosPorArea($todosLosPedidos, $areasPermitidas);
        $paginacion = $this->paginarPedidos($pedidosFiltradosPorRol, $request);

        if ($request->query('view') === WarehouseConstants::VIEW_DETAILS) {
            return $this->procesarVistaDetallada($paginacion, $rolesDelUsuario, $areasPermitidas);
        }

        return $this->procesarVistaLista($paginacion, $pedidosFiltradosPorRol);
    }

    public function obtenerPedidosEntregadosPaginados(Request $request): array
    {
        $usuario = auth()->user();
        $rolesDelUsuario = $usuario->getRoleNames()->toArray();

        $areasPermitidas = $this->roleService->obtenerAreasPermitidas($rolesDelUsuario);

        $numerosEntregados = PedidoProduccion::where('estado', 'Entregado')
            ->whereNotNull('numero_pedido')
            ->where('numero_pedido', '!=', '')
            ->pluck('numero_pedido')
            ->filter(fn ($n) => !empty($n))
            ->unique()
            ->values();

        $todosLosPedidos = ReciboPrenda::with(['asesor'])
            ->whereIn('numero_pedido', $numerosEntregados)
            ->orderBy('updated_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $pedidosFiltradosPorRol = $this->filtrarPedidosPorArea($todosLosPedidos, $areasPermitidas);
        $paginacion = $this->paginarPedidos($pedidosFiltradosPorRol, $request);

        $pedidosPorPagina = [];
        $pedidosAgrupados = $pedidosFiltradosPorRol->groupBy('numero_pedido');

        foreach ($pedidosAgrupados as $numeroPedido => $pedidosDelNumero) {
            $primerPedido = $pedidosDelNumero->first();
            $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

            $estadosPedido = $this->estadoCalculator->calcular($numeroPedido);

            $tieneItemsPendientes = $estadosPedido['tiene_pendientes'];
            $todosEntregados = $estadosPedido['todos_entregados'];
            $todosPendientes = $estadosPedido['todos_pendientes'];

            $userId = auth()->id();
            $vistoPorUsuario = PedidoVistoSupervisor::where('pedido_id', $primerPedido->id)
                ->where('user_id', $userId)
                ->first();

            $pedidoRevisado = PedidoRevisado::where('pedido_id', $numeroPedido)
                ->where('user_id', $userId)
                ->first();

            $pedidosPorPagina[] = [
                'id' => $numeroPedido,
                'numero_pedido' => $numeroPedido,
                'cliente' => $primerPedido->cliente ?? WarehouseConstants::DEFAULT_NA,
                'asesor' => $primerPedido->asesor?->nombre ?? $primerPedido->asesor?->name ?? WarehouseConstants::DEFAULT_NA,
                'estado' => $pedidoProduccion?->estado ?? $primerPedido->estado,
                'fecha_pedido' => $primerPedido->created_at ?? $primerPedido->fecha_pedido,
                'fecha_actualizacion' => $this->historialService->obtenerUltimaActualizacionPrendas($numeroPedido) ?? $primerPedido->created_at,
                'cantidad_items' => $pedidosDelNumero->count(),
                'viewed_at' => $vistoPorUsuario?->created_at,
                'pedido_revisado' => !empty($pedidoRevisado),
                'tiene_pendientes' => $tieneItemsPendientes,
                'todos_pendientes' => $todosPendientes,
                'todos_entregados' => $todosEntregados,
            ];
        }

        if ($request->query('view') === WarehouseConstants::VIEW_DETAILS) {
            return $this->procesarVistaDetallada($paginacion, $rolesDelUsuario, $areasPermitidas);
        }

        return [
            'view_type' => WarehouseConstants::VIEW_LIST,
            'pedidos_por_pagina' => $pedidosPorPagina,
            'total_pedidos' => count($pedidosPorPagina),
            'pagina_actual' => $paginacion['pagina_actual'],
            'por_pagina' => $paginacion['por_pagina'],
        ];
    }

    public function obtenerDetallePedido(int $pedidoId, bool $paraDespacho = false): array
    {
        $usuario = auth()->user();
        $rolesDelUsuario = $usuario->getRoleNames()->toArray();
        $areasPermitidas = $this->roleService->obtenerAreasPermitidas($rolesDelUsuario);
        $estadosPermitidos = $this->obtenerEstadosPermitidos();

        \Log::info('[obtenerDetallePedido] Iniciando busqueda', ['pedidoId' => $pedidoId]);

        $primerRecibo = ReciboPrenda::where('numero_pedido', $pedidoId)
            ->orWhere('id', $pedidoId)
            ->first();

        \Log::info('[obtenerDetallePedido] ReciboPrenda encontrado', [
            'tiene_recibo' => !!$primerRecibo,
            'recibo_numero_pedido' => $primerRecibo?->numero_pedido ?? 'null',
            'recibo_id' => $primerRecibo?->id ?? 'null',
        ]);

        if (!$primerRecibo || empty($primerRecibo->numero_pedido)) {
            $pedidoProduccion = PedidoProduccion::where('numero_pedido', $pedidoId)
                ->orWhere('id', $pedidoId)
                ->first();

            \Log::info('[obtenerDetallePedido] ReciboPrenda sin numero_pedido, buscando PedidoProduccion', [
                'tiene_pedido' => !!$pedidoProduccion,
                'pedido_numero' => $pedidoProduccion?->numero_pedido ?? 'null',
            ]);

            if (!$pedidoProduccion) {
                throw new \Exception("Pedido no encontrado (numero_pedido: $pedidoId)");
            }

            $numeroPedido = $pedidoProduccion->numero_pedido;

            $primerRecibo = new ReciboPrenda([
                'id' => $pedidoProduccion->id,
                'numero_pedido' => $numeroPedido,
                'estado' => $pedidoProduccion->estado,
                'cliente' => $pedidoProduccion->cliente,
                'asesor_id' => $pedidoProduccion->asesor_id,
            ]);
        } else {
            $numeroPedido = $primerRecibo?->numero_pedido;
            if (!$numeroPedido) {
                throw new \Exception("ReciboPrenda sin numero_pedido (ID: $pedidoId)");
            }
        }

        $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

        $estadoPP = strtoupper(trim($pedidoProduccion?->estado ?? ''));
        $esAnulada = str_starts_with($estadoPP, 'ANULAD');
        $esEntregada = $estadoPP === 'ENTREGADO';

        \Log::info('[obtenerDetallePedido] Estado del pedido', [
            'numero_pedido' => $numeroPedido,
            'estado' => $estadoPP,
            'es_anulada' => $esAnulada,
            'es_entregada' => $esEntregada,
        ]);

        $recibos = ($esAnulada || $esEntregada)
            ? ReciboPrenda::with(['asesor'])->where('numero_pedido', $numeroPedido)->get()
            : $this->bodegaRepository->obtenerRecibosPedido($numeroPedido, $estadosPermitidos);

        \Log::info('[obtenerDetallePedido] Recibos obtenidos', ['count' => $recibos->count(), 'es_anulada' => $esAnulada, 'es_entregada' => $esEntregada]);

        $items = $paraDespacho
            ? $this->detalleService->procesarItemsPedidoParaDespacho($recibos, $rolesDelUsuario, $areasPermitidas)
            : $this->detalleService->procesarItemsPedido($recibos, $rolesDelUsuario, $areasPermitidas);

        $items = $this->detalleService->calcularRowspans($items);

        return [
            'pedido' => [
                'id' => $primerRecibo->id ?? null,
                'numero_pedido' => $numeroPedido ?? null,
                'estado' => $pedidoProduccion?->estado ?? $primerRecibo?->estado ?? 'Desconocido',
                'cliente' => $primerRecibo?->cliente ?? $pedidoProduccion?->cliente ?? 'Cliente no especificado',
                'asesor' => $primerRecibo?->asesor?->nombre ?? $primerRecibo?->asesor?->name ?? null,
            ],
            'items' => $items,
        ];
    }

    public function obtenerDatosFactura(int $id): array
    {
        try {
            $reciboPrenda = ReciboPrenda::select('id', 'numero_pedido')->findOrFail($id);
            $pedido = PedidoProduccion::where('numero_pedido', $reciboPrenda->numero_pedido)->firstOrFail();
            $datos = $this->pedidoRepository->obtenerDatosFactura($pedido->id);

            return [
                'success' => true,
                'data' => $datos,
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'success' => false,
                'message' => 'Pedido no encontrado',
            ];
        } catch (\Exception $e) {
            \Log::error('[ERROR] obtenerDatosFactura | ID: ' . $id . ' | ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al obtener datos',
                'debug' => config('app.debug') ? $e->getMessage() : null,
            ];
        }
    }

    private function obtenerEstadosPermitidos(): array
    {
        return WarehouseConstants::getEstadosPermitidos();
    }

    private function filtrarPedidosPorArea(Collection $pedidos, array $areasPermitidas): Collection
    {
        return $pedidos->filter(function ($item) use ($areasPermitidas) {
            $bdDetalles = BodegaDetallesTalla::where('numero_pedido', $item->numero_pedido)->get();

            if ($bdDetalles->isEmpty()) {
                return in_array(null, $areasPermitidas);
            }

            foreach ($bdDetalles as $detalle) {
                if (in_array($detalle->area, $areasPermitidas)) {
                    return true;
                }
            }

            return false;
        })->values();
    }

    private function filtrarPedidosAnulados(Collection $pedidos): Collection
    {
        $numerosPedido = $pedidos->pluck('numero_pedido')
            ->filter(fn ($n) => !empty($n))
            ->unique()
            ->values();

        if ($numerosPedido->isEmpty()) {
            return $pedidos;
        }

        $numerosAnulados = PedidoProduccion::query()
            ->whereIn('numero_pedido', $numerosPedido)
            ->whereRaw('UPPER(TRIM(estado)) = ?', [WarehouseConstants::STATE_CANCELLED_UPPER])
            ->pluck('numero_pedido')
            ->unique();

        if ($numerosAnulados->isEmpty()) {
            return $pedidos;
        }

        return $pedidos->reject(fn ($p) => $numerosAnulados->contains($p->numero_pedido))->values();
    }

    private function paginarPedidos(Collection $pedidos, Request $request): array
    {
        $search = $request->get('search');
        if ($search) {
            $pedidos = $pedidos->filter(function ($pedido) use ($search) {
                if (stripos($pedido->numero_pedido, $search) !== false) {
                    return true;
                }

                if ($pedido->cliente && stripos($pedido->cliente, $search) !== false) {
                    return true;
                }

                return false;
            });
        }

        $pedidos = $this->aplicarFiltrosAvanzados($pedidos, $request);

        $numerosPedidosUnicos = $pedidos->pluck('numero_pedido')->unique()->values();
        $totalPedidos = $numerosPedidosUnicos->count();

        $paginaActual = $request->get('page', 1);
        $porPagina = WarehouseConstants::ITEMS_PER_PAGE;
        $offset = ($paginaActual - 1) * $porPagina;

        $pedidosPaginados = $numerosPedidosUnicos->slice($offset, $porPagina);

        $paginacion = new LengthAwarePaginator(
            $pedidosPaginados,
            $totalPedidos,
            $porPagina,
            $paginaActual,
            [
                'path' => route('gestion-bodega.pedidos'),
                'query' => $request->query(),
            ]
        );

        return [
            'pedidos_paginados' => $pedidosPaginados,
            'total_pedidos' => $totalPedidos,
            'pagina_actual' => $paginaActual,
            'por_pagina' => $porPagina,
            'paginacion_obj' => $paginacion,
        ];
    }

    private function aplicarFiltrosAvanzados(Collection $pedidos, Request $request): Collection
    {
        $filtroNumeroPedido = $request->get('filtro_numero_pedido');
        if ($filtroNumeroPedido) {
            $pedidos = $pedidos->filter(function ($pedido) use ($filtroNumeroPedido) {
                return stripos($pedido->numero_pedido, $filtroNumeroPedido) !== false;
            });
        }

        $filtroEstado = $request->get('filtro_estado');
        if ($filtroEstado) {
            $pedidos = $pedidos->filter(function ($pedido) use ($filtroEstado) {
                $estadoPedido = strtoupper(trim($pedido->estado ?? ''));
                return $estadoPedido === strtoupper(trim($filtroEstado));
            });
        }

        $filtroAsesor = $request->get('filtro_asesor');
        if ($filtroAsesor) {
            $pedidos = $pedidos->filter(function ($pedido) use ($filtroAsesor) {
                $nombreAsesor = $pedido->asesor?->nombre ?? $pedido->asesor?->name ?? '';
                return stripos($nombreAsesor, $filtroAsesor) !== false;
            });
        }

        $filtroCliente = $request->get('filtro_cliente');
        if ($filtroCliente) {
            $pedidos = $pedidos->filter(function ($pedido) use ($filtroCliente) {
                return $pedido->cliente && stripos($pedido->cliente, $filtroCliente) !== false;
            });
        }

        $filtroFechaDesde = $request->get('filtro_fecha_desde');
        $filtroFechaHasta = $request->get('filtro_fecha_hasta');

        if ($filtroFechaDesde || $filtroFechaHasta) {
            $pedidos = $pedidos->filter(function ($pedido) use ($filtroFechaDesde, $filtroFechaHasta) {
                $fechaPedido = $pedido->created_at ?? $pedido->fecha_pedido;

                if (!$fechaPedido) {
                    return false;
                }

                $fechaPedido = Carbon::parse($fechaPedido);

                if ($filtroFechaDesde) {
                    $fechaDesde = Carbon::parse($filtroFechaDesde)->startOfDay();
                    if ($fechaPedido->lt($fechaDesde)) {
                        return false;
                    }
                }

                if ($filtroFechaHasta) {
                    $fechaHasta = Carbon::parse($filtroFechaHasta)->endOfDay();
                    if ($fechaPedido->gt($fechaHasta)) {
                        return false;
                    }
                }

                return true;
            });
        }

        return $pedidos;
    }

    private function procesarVistaDetallada(array $paginacion, array $rolesDelUsuario, array $areasPermitidas): array
    {
        return [
            'view_type' => WarehouseConstants::VIEW_DETAILS,
            'pagination' => $paginacion,
        ];
    }

    private function procesarVistaLista(array $paginacion, Collection $pedidosFiltrados): array
    {
        $pedidosPorPagina = [];
        foreach ($paginacion['pedidos_paginados'] as $numeroPedido) {
            $pedidosDelNumero = $pedidosFiltrados->filter(fn ($p) => $p->numero_pedido === $numeroPedido)->values();
            if ($pedidosDelNumero->isNotEmpty()) {
                $primerPedido = $pedidosDelNumero->first();
                $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

                $estadosPedido = $this->estadoCalculator->calcular($numeroPedido);

                $tieneItemsPendientes = $estadosPedido['tiene_pendientes'];
                $todosEntregados = $estadosPedido['todos_entregados'];
                $todosPendientes = $estadosPedido['todos_pendientes'];

                $userId = auth()->id();
                $vistoPorUsuario = PedidoVistoSupervisor::where('pedido_id', $primerPedido->id)
                    ->where('user_id', $userId)
                    ->first();

                $pedidoRevisado = PedidoRevisado::where('pedido_id', $numeroPedido)
                    ->where('user_id', $userId)
                    ->first();

                $pedidosPorPagina[] = [
                    'id' => $numeroPedido,
                    'numero_pedido' => $numeroPedido,
                    'cliente' => $primerPedido->cliente ?? WarehouseConstants::DEFAULT_NA,
                    'asesor' => $primerPedido->asesor?->nombre ?? $primerPedido->asesor?->name ?? WarehouseConstants::DEFAULT_NA,
                    'estado' => $pedidoProduccion?->estado ?? $primerPedido->estado,
                    'fecha_pedido' => $primerPedido->created_at ?? $primerPedido->fecha_pedido,
                    'fecha_actualizacion' => $this->historialService->obtenerUltimaActualizacionPrendas($numeroPedido) ?? $primerPedido->created_at,
                    'cantidad_items' => $pedidosDelNumero->count(),
                    'viewed_at' => $vistoPorUsuario?->created_at,
                    'pedido_revisado' => !empty($pedidoRevisado),
                    'tiene_pendientes' => $tieneItemsPendientes,
                    'todos_pendientes' => $todosPendientes,
                    'todos_entregados' => $todosEntregados,
                ];
            }
        }

        return [
            'view_type' => WarehouseConstants::VIEW_LIST,
            'pedidos_por_pagina' => $pedidosPorPagina,
            'total_pedidos' => $paginacion['total_pedidos'],
            'pagina_actual' => $paginacion['pagina_actual'],
            'por_pagina' => $paginacion['por_pagina'],
        ];
    }

}
