<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\ListOrdersRequest;
use App\Application\SupervisorPedidos\DTOs\ListOrdersResponse;
use App\Domain\Pedidos\Services\PedidoProduccionDomainService;
use App\Models\PedidoProduccion;
use App\Models\SeleccionPedido;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ListOrdersUseCase
{
    private PedidoProduccionDomainService $domainService;

    public function __construct(PedidoProduccionDomainService $domainService)
    {
        $this->domainService = $domainService;
    }

    public function execute(ListOrdersRequest $request): ListOrdersResponse
    {
        try {
            $query = PedidoProduccion::withTrashed()->with(['asesora', 'prendas', 'epps', 'cotizacion']);

            // Aplicar todos los filtros
            $this->applyStatusFilters($query);
            $this->applyHiddenFilter($query, $request);
            $this->applyPendingNumberFilter($query);
            $this->applyEppOnlyFilter($query);
            $this->applyApprovalFilter($query, $request);
            $this->applySearchFilter($query, $request);
            $this->applyColumnFilters($query, $request);
            $this->applyDateFilters($query, $request);

            // Ordenar y paginar
            $ordenes = $this->orderAndPaginate($query, $request);

            // Pre-computar datos de negocio para cada orden (evitar lógica en vistas/modelos)
            $ordenes->getCollection()->each(function($orden) {
                $orden->es_solo_epp = $this->domainService->esSoloEpp($orden);
            });

            // Obtener estados únicos
            $estados = PedidoProduccion::distinct()
                                       ->pluck('estado')
                                       ->filter()
                                       ->values()
                                       ->toArray();

            // Cargar pedidos seleccionados
            $pedidosSeleccionados = $this->getSelectedOrders();

            Log::info('[ListOrdersUseCase] Retrieved ' . $ordenes->count() . ' orders with ' . count($estados) . ' states');

            return new ListOrdersResponse($ordenes, $estados, $pedidosSeleccionados);

        } catch (\Exception $e) {
            Log::error('[ListOrdersUseCase] Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function applyStatusFilters($query): void
    {
        // EXCLUIR pedidos en estado pendiente_cartera o RECHAZADO_CARTERA
        $query->whereNotIn('estado', ['pendiente_cartera', 'RECHAZADO_CARTERA']);
    }

    private function applyHiddenFilter($query, ListOrdersRequest $request): void
    {
        if ($request->getMostrar() === 'ocultos') {
            $query->whereNotNull('ocultado_en');
        } else {
            $query->whereNull('ocultado_en');
        }
    }

    private function applyPendingNumberFilter($query): void
    {
        $query->whereNotNull('numero_pedido')
              ->where('numero_pedido', '!=', '');
    }

    private function applyEppOnlyFilter($query): void
    {
        $query->where(function($q) {
            $q->whereHas('prendas')
              ->orWhere(function($subQuery) {
                  $subQuery->whereDoesntHave('prendas')
                           ->whereDoesntHave('epps');
              });
        });
    }

    private function applyApprovalFilter($query, ListOrdersRequest $request): void
    {
        if (!$request->getAprobacion()) {
            return;
        }

        if ($request->getAprobacion() === 'pendiente') {
            $query->whereIn('estado', ['PENDIENTE_SUPERVISOR', 'No iniciado']);
            
            if ($request->getTipo() === 'logo') {
                $query->whereHas('cotizacion', function($q) {
                    $q->where('tipo', 'logo');
                });
            }
        } elseif ($request->getAprobacion() === 'aprobadas') {
            $query->whereIn('estado', ['Pendiente', 'En Ejecución', 'Finalizada', 'Anulada']);
        }
    }

    private function applySearchFilter($query, ListOrdersRequest $request): void
    {
        if (!$request->getBusqueda()) {
            return;
        }

        $busqueda = $request->getBusqueda();
        $query->where(function($q) use ($busqueda) {
            $q->where('numero_pedido', 'like', '%' . $busqueda . '%')
              ->orWhere('cliente', 'like', '%' . $busqueda . '%');
        });
    }

    private function applyColumnFilters($query, ListOrdersRequest $request): void
    {
        if ($request->getNumero()) {
            $numeros = explode(',', $request->getNumero());
            $query->whereIn('numero_pedido', $numeros);
        }

        if ($request->getCliente()) {
            $clientes = explode(',', $request->getCliente());
            $query->whereIn('cliente', $clientes);
        }

        if ($request->getFormaPago()) {
            $formasPago = explode(',', $request->getFormaPago());
            $query->whereIn('forma_de_pago', $formasPago);
        }

        if ($request->getEstado()) {
            $estado = $request->getEstado();
            if ($estado === 'En Producción') {
                $query->whereIn('estado', ['No iniciado', 'En Ejecución']);
            } else {
                $query->where('estado', $estado);
            }
        } else {
            $query->where('estado', '!=', 'Anulada');
        }

        if ($request->getAsesora()) {
            $asesoras = explode(',', $request->getAsesora());
            $query->whereHas('asesora', function($q) use ($asesoras) {
                $q->whereIn('name', $asesoras);
            });
        }
    }

    private function applyDateFilters($query, ListOrdersRequest $request): void
    {
        if ($request->getFechaDesde()) {
            $query->whereDate('created_at', '>=', $request->getFechaDesde());
        }

        if ($request->getFechaHasta()) {
            $query->whereDate('created_at', '<=', $request->getFechaHasta());
        }
    }

    private function orderAndPaginate($query, ListOrdersRequest $request)
    {
        return $query->orderByRaw('
                (SELECT MAX(created_at) FROM pedido_anexos_historial WHERE pedido_produccion_id = pedidos_produccion.id) IS NULL ASC
            ')
                     ->orderByRaw('
                (SELECT MAX(created_at) FROM pedido_anexos_historial WHERE pedido_produccion_id = pedidos_produccion.id) DESC
            ')
                     ->orderBy('updated_at', 'desc')
                     ->orderBy('numero_pedido', 'desc')
                     ->paginate($request->getPerPage(), ['*'], 'page', $request->getPage())
                     ->appends(array_filter([
                         'mostrar' => request()->input('mostrar'),
                         'aprobacion' => request()->input('aprobacion'),
                         'tipo' => request()->input('tipo'),
                         'busqueda' => request()->input('busqueda'),
                         'numero' => request()->input('numero'),
                         'cliente' => request()->input('cliente'),
                         'forma_pago' => request()->input('forma_pago'),
                         'estado' => request()->input('estado'),
                         'asesora' => request()->input('asesora'),
                         'fecha_desde' => request()->input('fecha_desde'),
                         'fecha_hasta' => request()->input('fecha_hasta'),
                     ]));
    }

    private function getSelectedOrders(): array
    {
        if (!Auth::check()) {
            return [];
        }

        return SeleccionPedido::where('user_id', Auth::id())
            ->where('seleccionado', true)
            ->pluck('pedido_id')
            ->toArray();
    }
}
