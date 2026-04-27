<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Domain\Pedidos\Services\PedidoDetalleReadService;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Models\PrendaEntrega;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PedidoDetalleReadServiceImpl implements PedidoDetalleReadService
{
    public function findPedidoByIdOrNumero(int $idONumero): ?PedidoProduccion
    {
        return PedidoProduccion::find($idONumero)
            ?? PedidoProduccion::where('numero_pedido', $idONumero)->first();
    }

    public function findPedidoById(int $pedidoId): ?PedidoProduccion
    {
        return PedidoProduccion::find($pedidoId);
    }

    public function findPedidoByIdConRelaciones(int $pedidoId, bool $filtrarPrendasBodega): ?PedidoProduccion
    {
        return PedidoProduccion::with([
            'prendas' => function ($q) use ($filtrarPrendasBodega) {
                if ($filtrarPrendasBodega) {
                    $q->where('de_bodega', false);
                }

                $q->with([
                    'tallas',
                    'variantes.tipoManga',
                    'variantes.tipoBroche',
                    'anchoMetraje',
                    'coloresTelas' => function ($q2) {
                        $q2->with([
                            'color',
                            'tela',
                        ]);
                    },
                    'procesos' => function ($q3) {
                        $q3->with([
                            'tipoProceso',
                            'tallas',
                        ])->orderBy('created_at', 'desc');
                    },
                ]);
            },
            'epps' => function ($q) {
                $q->withTrashed()
                    ->with([
                        'epp',
                    ]);
            },
        ])->find($pedidoId);
    }

    public function getProcesoTallasDetalle(int $procesoDetalleId): Collection
    {
        return DB::table('pedidos_procesos_prenda_tallas')
            ->where('proceso_prenda_detalle_id', $procesoDetalleId)
            ->get(['genero', 'talla', 'cantidad', 'es_sobremedida', 'ubicaciones', 'observaciones']);
    }

    public function getProcesoTallasConObservaciones(int $procesoDetalleId): Collection
    {
        return DB::table('pedidos_procesos_prenda_tallas')
            ->where('proceso_prenda_detalle_id', $procesoDetalleId)
            ->whereNotNull('observaciones')
            ->where('observaciones', '!=', '')
            ->get(['genero', 'talla', 'observaciones']);
    }

    public function getAnchoPrenda(int $pedidoId, int $prendaId): ?object
    {
        return DB::table('pedido_ancho_general')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_pedido_id', $prendaId)
            ->latest('created_at')
            ->first();
    }

    public function getMetrajesPrenda(int $pedidoId, int $prendaId): Collection
    {
        return DB::table('pedido_metraje_color')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_pedido_id', $prendaId)
            ->latest('created_at')
            ->get();
    }

    public function getConsecutivosPrenda(int $pedidoId, int $prendaId): Collection
    {
        $consecutivos = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where(function ($query) use ($prendaId) {
                $query->where('prenda_id', $prendaId)
                    ->orWhereNull('prenda_id');
            })
            ->select([
                'id',
                'tipo_recibo',
                'consecutivo_actual',
                'consecutivo_inicial',
                'activo',
                'estado',
                'area',
                'created_at',
            ])
            ->get();

        return $consecutivos;
    }

    public function getParcialesPrenda(int $pedidoId, int $prendaId): Collection
    {
        $query = DB::table('pedidos_parciales as pp')
            ->where('pp.pedido_produccion_id', $pedidoId)
            ->where('pp.prenda_pedido_id', $prendaId)
            ->whereNull('pp.deleted_at')
            ->select([
                'pp.id',
                'pp.tipo_recibo',
                'pp.consecutivo_actual',
                'pp.consecutivo_inicial',
                'pp.activo',
                'pp.estado',
                'pp.created_at',
            ]);

        if (Schema::hasColumn('consecutivos_recibos_pedidos', 'pedido_parcial_id')) {
            $query->leftJoin('consecutivos_recibos_pedidos as crp', function ($join) {
                $join->on('crp.pedido_parcial_id', '=', 'pp.id')
                    ->where('crp.activo', 1)
                    ->whereRaw("UPPER(COALESCE(crp.estado, '')) <> 'ANULADO'");
            })->addSelect('crp.id as consecutivo_recibo_id');
        } else {
            $query->addSelect(DB::raw('NULL as consecutivo_recibo_id'));
        }

        return $query->get();
    }

    public function findReciboCosturaByPedidoId(int $pedidoId): ?object
    {
        return ConsecutivoReciboPedido::query()
            ->where('pedido_produccion_id', $pedidoId)
            ->where('tipo_recibo', 'COSTURA')
            ->first();
    }

    public function getFechaEstimadaMasLejanaByPedidoId(int $pedidoId): ?string
    {
        $maxFecha = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $pedidoId)
            ->whereNotNull('fecha_estimada_de_entrega')
            ->max('fecha_estimada_de_entrega');

        return $maxFecha ? (string) $maxFecha : null;
    }

    public function getTallasProceso(int $procesoDetalleId): Collection
    {
        return DB::table('pedidos_procesos_prenda_tallas')
            ->where('proceso_prenda_detalle_id', $procesoDetalleId)
            ->get();
    }

    public function getColoresByProcesoTalla(int $procesoTallaId): Collection
    {
        return DB::table('pedidos_procesos_prenda_talla_colores')
            ->where('pedidos_procesos_prenda_talla_id', $procesoTallaId)
            ->get();
    }

    public function getTallasColoresPrenda(int $prendaId): Collection
    {
        return DB::table('prenda_pedido_talla_colores as pptc')
            ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->where('ppt.prenda_pedido_id', $prendaId)
            ->select(['ppt.genero', 'ppt.talla', 'pptc.color_nombre', 'pptc.cantidad'])
            ->get();
    }

    public function getColoresPorTallaPrenda(int $prendaId): Collection
    {
        return DB::table('prenda_pedido_talla_colores as pptc')
            ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->where('ppt.prenda_pedido_id', $prendaId)
            ->select(
                'pptc.id as talla_color_id',
                'ppt.id as talla_id',
                'ppt.talla',
                'ppt.genero',
                'pptc.color_nombre',
                'pptc.cantidad'
            )
            ->get();
    }

    public function getImagenRutasTallaColorPrenda(int $prendaId): Collection
    {
        return DB::table('prenda_pedido_talla_colores as ptc')
            ->join('prenda_pedido_tallas as pt', 'ptc.prenda_pedido_talla_id', '=', 'pt.id')
            ->where('pt.prenda_pedido_id', $prendaId)
            ->whereNotNull('ptc.imagen_ruta')
            ->pluck('ptc.imagen_ruta');
    }

    public function getTallaColoresDetallePrenda(int $prendaId): Collection
    {
        return DB::table('prenda_pedido_talla_colores as pptc')
            ->join('prenda_pedido_tallas as ppt', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->where('ppt.prenda_pedido_id', $prendaId)
            ->select([
                'ppt.genero',
                'ppt.talla',
                'pptc.tela_nombre',
                'pptc.color_nombre',
                'pptc.cantidad',
                'pptc.referencia',
                'pptc.observaciones',
                'pptc.imagen_ruta',
            ])
            ->get();
    }

    public function findPrendaEntrega(int $prendaId): ?object
    {
        return PrendaEntrega::query()->where('prenda_pedido_id', $prendaId)->first();
    }

    public function getPrendaEntregaEstado(int $prendaId): array
    {
        static $tienePedidoParcialIdCache = null;
        $tienePedidoParcialId = $tienePedidoParcialIdCache ??= Schema::hasColumn('consecutivos_recibos_pedidos', 'pedido_parcial_id');

        $columns = ['id'];
        if ($tienePedidoParcialId) {
            $columns[] = 'pedido_parcial_id';
        }

        $recibos = DB::table('consecutivos_recibos_pedidos')
            ->where('prenda_id', $prendaId)
            ->where('activo', 1)
            ->whereRaw("UPPER(COALESCE(estado, '')) <> 'ANULADO'")
            ->get($columns);

        $totalRecibos = $recibos->count();
        if ($totalRecibos === 0) {
            return [
                'total_recibos' => 0,
                'recibos_entregados' => 0,
                'recibos_con_movimiento' => 0,
                'estado_entrega' => 'pendiente',
                'completa' => false,
            ];
        }

        $cantidadBasePrenda = (int) DB::table('prenda_pedido_tallas')
            ->where('prenda_pedido_id', $prendaId)
            ->sum('cantidad');

        $cantidadesEntregadas = DB::table('prenda_entrega_movimientos')
            ->where('prenda_pedido_id', $prendaId)
            ->select('consecutivo_recibo_id', DB::raw('SUM(cantidad_entregada) as total_entregado'))
            ->groupBy('consecutivo_recibo_id')
            ->pluck('total_entregado', 'consecutivo_recibo_id');

        $cantidadesParciales = collect();
        if ($tienePedidoParcialId) {
            $pedidoParcialIds = $recibos
                ->pluck('pedido_parcial_id')
                ->filter()
                ->unique()
                ->values();

            if ($pedidoParcialIds->isNotEmpty()) {
                $cantidadesParciales = DB::table('pedidos_parciales_tallas')
                    ->whereIn('pedido_parcial_id', $pedidoParcialIds)
                    ->select('pedido_parcial_id', DB::raw('SUM(cantidad) as total_cantidad'))
                    ->groupBy('pedido_parcial_id')
                    ->pluck('total_cantidad', 'pedido_parcial_id');
            }
        }

        $recibosEntregados = 0;
        $recibosConMovimiento = 0;

        foreach ($recibos as $recibo) {
            $pedidoParcialId = $tienePedidoParcialId ? ($recibo->pedido_parcial_id ?? null) : null;
            $cantidadTotal = $pedidoParcialId
                ? (int) ($cantidadesParciales[$pedidoParcialId] ?? 0)
                : $cantidadBasePrenda;

            $cantidadEntregada = (int) ($cantidadesEntregadas[$recibo->id] ?? 0);

            if ($cantidadEntregada > 0) {
                $recibosConMovimiento++;
            }

            if ($cantidadTotal > 0 && $cantidadEntregada >= $cantidadTotal) {
                $recibosEntregados++;
            }
        }

        $completa = $totalRecibos > 0 && $recibosEntregados >= $totalRecibos;
        $estado = 'pendiente';

        if ($completa) {
            $estado = 'completo';
        } elseif ($recibosConMovimiento > 0) {
            $estado = 'parcial';
        }

        return [
            'total_recibos' => (int) $totalRecibos,
            'recibos_entregados' => (int) $recibosEntregados,
            'recibos_con_movimiento' => (int) $recibosConMovimiento,
            'estado_entrega' => $estado,
            'completa' => $completa,
        ];
    }

    public function getRecibosParcialesPrenda(int $pedidoId, int $prendaId): Collection
    {
        return DB::table('pedidos_parciales')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_pedido_id', $prendaId)
            ->orderBy('tipo_recibo', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getReciboParcialTallas(int $pedidoParcialId): Collection
    {
        return DB::table('pedidos_parciales_tallas')
            ->where('pedido_parcial_id', $pedidoParcialId)
            ->get();
    }

    public function getPedidoEppImagenes(int $pedidoEppId): Collection
    {
        return DB::table('pedido_epp_imagenes')
            ->where('pedido_epp_id', $pedidoEppId)
            ->orderBy('orden', 'asc')
            ->get(['ruta_web', 'ruta_original', 'principal', 'orden']);
    }
}
