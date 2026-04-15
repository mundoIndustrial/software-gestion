<?php

namespace App\Infrastructure\Pedidos\Persistence\Eloquent;

use App\Application\Pedidos\Services\FacturaPedidoService;
use App\Application\Pedidos\Services\ReciboPedidoService;
use App\Domain\Pedidos\ReadModels\PaginatedPedidosResult;
use App\Domain\Pedidos\ReadModels\PedidoBorradorRef;
use App\Domain\Pedidos\ReadModels\PedidoEppRef;
use App\Domain\Pedidos\ReadModels\PedidoProduccionListItem;
use App\Domain\Pedidos\ReadModels\PedidoNumeroRef;
use App\Domain\Pedidos\ReadModels\PedidoPrendaRef;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Infrastructure\Pedidos\Persistence\Eloquent\Concerns\GestionaTallasRelacional;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

class EloquentPedidoProduccionRepository implements PedidoProduccionReadRepository
{
    use GestionaTallasRelacional;

    public function __construct(
        private FacturaPedidoService $facturaService,
        private ReciboPedidoService $reciboService
    ) {}

    public function findByNumeroPedido(string $numeroPedido): ?PedidoNumeroRef
    {
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

        if (!$pedido) {
            return null;
        }

        return new PedidoNumeroRef(
            pedidoId: (int) $pedido->id,
            numeroPedido: (int) $pedido->numero_pedido,
            clienteId: $pedido->cliente_id !== null ? (int) $pedido->cliente_id : null,
            asesorId: $pedido->asesor_id !== null ? (int) $pedido->asesor_id : null,
            estado: (string) $pedido->estado,
        );
    }

    public function obtenerPedidosAsesor(array $filtros = []): PaginatedPedidosResult
    {
        $page = max(1, (int) ($filtros['page'] ?? 1));
        $perPage = max(1, (int) ($filtros['per_page'] ?? 15));
        $queryParams = collect($filtros)
            ->except(['page', 'per_page'])
            ->toArray();

        $fechaMaximaRecibosSubquery = DB::table('consecutivos_recibos_pedidos')
            ->selectRaw('pedido_produccion_id, MAX(fecha_estimada_de_entrega) as fecha_maxima_recibos')
            ->whereNotNull('fecha_estimada_de_entrega')
            ->groupBy('pedido_produccion_id');

        $query = PedidoProduccion::query()
            ->select([
                'pedidos_produccion.*',
                'pedidos_produccion.area',
                DB::raw('fecha_maxima_recibos_subquery.fecha_maxima_recibos as fecha_estimada_calculada'),
            ])
            ->leftJoinSub($fechaMaximaRecibosSubquery, 'fecha_maxima_recibos_subquery', function ($join) {
                $join->on('fecha_maxima_recibos_subquery.pedido_produccion_id', '=', 'pedidos_produccion.id');
            })
            ->with(['cotizacion', 'prendas']);

        if (!empty($filtros['asesor_id'])) {
            $query->where('asesor_id', $filtros['asesor_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        } else {
            // Comportamiento por defecto para listados generales:
            // excluir borradores salvo que se pidan explícitamente por filtro.
            $query->where('estado', '!=', 'Borrador');
        }

        if (!empty($filtros['sin_numero'])) {
            $query->where(function ($q) {
                $q->whereNull('numero_pedido')
                    ->orWhere('numero_pedido', '');
            });
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        if (!empty($filtros['search'])) {
            $search = trim((string) $filtros['search']);
            $query->where(function ($subquery) use ($search) {
                $subquery->where('numero_pedido', 'LIKE', "%{$search}%")
                    ->orWhere('cliente', 'LIKE', "%{$search}%")
                    ->orWhere('novedades', 'LIKE', "%{$search}%");
            });
        }

        $paginator = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->getCollection()
            ->map(fn(PedidoProduccion $pedido) => new PedidoProduccionListItem(
                id: (int) $pedido->id,
                numero_pedido: $pedido->numero_pedido !== null ? (int) $pedido->numero_pedido : null,
                cliente: $pedido->cliente,
                estado: $pedido->estado,
                area: $pedido->area,
                novedades: $pedido->novedades,
                forma_pago: $pedido->forma_de_pago,
                fecha_creacion: optional($pedido->created_at)?->format('Y-m-d H:i:s'),
                fecha_estimada: !empty($pedido->fecha_estimada_calculada)
                    ? (string) $pedido->fecha_estimada_calculada
                    : null,
                asesor_id: $pedido->asesor_id !== null ? (int) $pedido->asesor_id : null,
            ))
            ->all();

        return new PaginatedPedidosResult(
            items: $items,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            path: $paginator->path(),
            query: $queryParams,
        );
    }

    public function perteneceAlAsesor(int $pedidoId, int $asesorId): bool
    {
        return PedidoProduccion::where('id', $pedidoId)
            ->where('asesor_id', $asesorId)
            ->exists();
    }

    public function actualizarCantidadTotal(string $numeroPedido): void
    {
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

        if ($pedido) {
            $cantidadTotal = $pedido->prendas()->sum('cantidad');
            $pedido->update(['cantidad_total' => $cantidadTotal]);
        }
    }

    public function obtenerDatosFactura(int $pedidoId, bool $paraCartera = false): array
    {
        return $this->facturaService->obtenerDatosFactura($pedidoId, $paraCartera);
    }

    public function obtenerDatosRecibos(int $pedidoId, bool $filtrarProcesosPendientes = false): array
    {
        return $this->reciboService->obtenerDatosRecibos($pedidoId, $filtrarProcesosPendientes);
    }

    public function obtenerPorIdYAsesor(int $pedidoId, int $asesorId): ?PedidoBorradorRef
    {
        $pedido = PedidoProduccion::where('id', $pedidoId)
            ->where('asesor_id', $asesorId)
            ->first();

        if (!$pedido) {
            return null;
        }

        return new PedidoBorradorRef(
            pedidoId: (int) $pedido->id,
            asesorId: (int) $pedido->asesor_id,
            numeroPedido: $pedido->numero_pedido !== null ? (int) $pedido->numero_pedido : null,
            estado: (string) $pedido->estado,
            cliente: $pedido->cliente,
        );
    }

    public function actualizarDatosBasicos(int $pedidoId, array $datos): void
    {
        PedidoProduccion::where('id', $pedidoId)->update($datos);
    }

    public function obtenerEppConImagenes(int $pedidoId, int $eppId): ?PedidoEppRef
    {
        $pedidoEpp = \App\Models\PedidoEpp::where('pedido_produccion_id', $pedidoId)
            ->where('epp_id', $eppId)
            ->with(['imagenes'])
            ->first();

        if (!$pedidoEpp) {
            return null;
        }

        return new PedidoEppRef(
            pedidoEppId: (int) $pedidoEpp->id,
            pedidoId: (int) $pedidoEpp->pedido_produccion_id,
            eppId: (int) $pedidoEpp->epp_id,
            cantidad: (int) $pedidoEpp->cantidad,
            observaciones: $pedidoEpp->observaciones,
            imagenesCount: $pedidoEpp->imagenes->count(),
        );
    }

    public function actualizarDatosEpp(int $pedidoEppId, array $datos): void
    {
        \App\Models\PedidoEpp::where('id', $pedidoEppId)->update($datos);
    }

    public function obtenerPrendaDelPedido(int $pedidoId, int $prendaId): ?PedidoPrendaRef
    {
        $prenda = \App\Models\PrendaPedido::query()
            ->where('pedido_produccion_id', $pedidoId)
            ->where('id', $prendaId)
            ->first();

        if (!$prenda) {
            return null;
        }

        return new PedidoPrendaRef(
            prendaId: (int) $prenda->id,
            pedidoId: (int) $prenda->pedido_produccion_id,
        );
    }

    public function obtenerPedidoPorId(int $pedidoId): ?array
    {
        $pedido = PedidoProduccion::query()->find($pedidoId);

        if ($pedido === null) {
            return null;
        }

        return [
            'id' => (int) $pedido->id,
            'numero_pedido' => $pedido->numero_pedido !== null ? (int) $pedido->numero_pedido : null,
            'cliente' => $pedido->cliente,
            'asesor_id' => $pedido->asesor_id !== null ? (int) $pedido->asesor_id : null,
            'estado' => (string) $pedido->estado,
            'forma_de_pago' => $pedido->forma_de_pago,
            'novedades' => $pedido->novedades,
            'created_at' => $pedido->created_at,
        ];
    }

    public function obtenerPedidoDetallePorNumero(int $numeroPedido): ?array
    {
        $pedido = PedidoProduccion::with('asesora', 'prendas')
            ->where('numero_pedido', $numeroPedido)
            ->first();

        if ($pedido === null) {
            return null;
        }

        return [
            'id' => (int) $pedido->id,
            'numero_pedido' => $pedido->numero_pedido !== null ? (int) $pedido->numero_pedido : null,
            'cliente' => $pedido->cliente,
            'created_at' => $pedido->created_at,
            'estado' => $pedido->estado,
            'forma_de_pago' => $pedido->forma_de_pago,
            'area' => $pedido->area,
            'novedades' => $pedido->novedades,
            'asesora' => $pedido->asesora?->name ?? '',
            'pedido_model' => $pedido,
        ];
    }

    public function obtenerTotalesPorNumeroPedido(int $numeroPedido): array
    {
        try {
            $totalCantidad = (int) DB::table('prendas')
                ->where('numero_pedido', $numeroPedido)
                ->sum('cantidad');
        } catch (\Throwable $e) {
            \Log::warning('Error calculando cantidad total de pedido', [
                'numero_pedido' => $numeroPedido,
                'error' => $e->getMessage(),
            ]);
            $totalCantidad = 0;
        }

        try {
            $totalEntregado = (int) DB::table('entregas')
                ->where('numero_pedido', $numeroPedido)
                ->sum('cantidad_entregada');
        } catch (\Throwable $e) {
            \Log::warning('Error calculando total entregado de pedido', [
                'numero_pedido' => $numeroPedido,
                'error' => $e->getMessage(),
            ]);
            $totalEntregado = 0;
        }

        return [
            'total_cantidad' => $totalCantidad,
            'total_entregado' => $totalEntregado,
        ];
    }
}
