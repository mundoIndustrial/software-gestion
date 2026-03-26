<?php

namespace App\Infrastructure\Pedidos\Persistence\Eloquent;

use App\Application\Pedidos\Services\FacturaPedidoService;
use App\Application\Pedidos\Services\ReciboPedidoService;
use App\Domain\Pedidos\ReadModels\PedidoBorradorRef;
use App\Domain\Pedidos\ReadModels\PedidoEppRef;
use App\Domain\Pedidos\ReadModels\PedidoNumeroRef;
use App\Domain\Pedidos\ReadModels\PedidoPrendaRef;
use App\Domain\Pedidos\Repositories\PedidoProduccionReadRepository;
use App\Infrastructure\Pedidos\Persistence\Eloquent\Concerns\GestionaTallasRelacional;
use App\Models\PedidoProduccion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    public function obtenerPorId(int $id): ?PedidoProduccion
    {
        return PedidoProduccion::with([
            'cotizacion.cliente',
            'cotizacion.tipoCotizacion',
            'prendas.variantes.tipoManga',
            'prendas.variantes.tipoBrocheBoton',
            'prendas.fotos',
            'prendas.fotosTelas',
            'prendas.coloresTelas.color',
            'prendas.coloresTelas.tela',
            'prendas.coloresTelas.fotos',
            'prendas.tallas',
            'prendas.tallas.coloresAsignados',
            'prendas.procesos',
            'prendas.procesos.tipoProceso',
            'prendas.procesos.imagenes',
            'prendas.procesos.tallas',
            'epps.imagenes',
        ])->find($id);
    }

    public function obtenerPedidosAsesor(array $filtros = []): LengthAwarePaginator
    {
        $query = PedidoProduccion::query()
            ->select([
                'pedidos_produccion.*',
                'pedidos_produccion.area',
            ])
            ->with(['cotizacion', 'prendas'])
            ->where('estado', '!=', 'Borrador');

        if (!empty($filtros['asesor_id'])) {
            $query->where('asesor_id', $filtros['asesor_id']);
        }

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
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

    public function obtenerDatosFactura(int $pedidoId): array
    {
        return $this->facturaService->obtenerDatosFactura($pedidoId);
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
}
