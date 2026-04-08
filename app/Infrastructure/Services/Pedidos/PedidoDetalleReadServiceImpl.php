<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Domain\Pedidos\Services\PedidoDetalleReadService;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use App\Models\PrendaEntrega;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
                    'fotos' => function ($q2) {
                        $q2->orderBy('orden', 'asc');
                    },
                    'coloresTelas' => function ($q2) {
                        $q2->with([
                            'color',
                            'tela',
                            'fotos' => function ($q3) {
                                $q3->orderBy('orden', 'asc');
                            },
                        ]);
                    },
                    'procesos' => function ($q3) {
                        $q3->with([
                            'tipoProceso',
                            'tallas',
                            'imagenes' => function ($q4) {
                                $q4->orderBy('orden', 'asc');
                            },
                        ])->orderBy('created_at', 'desc');
                    },
                ]);
            },
            'epps' => function ($q) {
                $q->withTrashed()
                    ->with([
                        'epp',
                        'imagenes' => function ($q2) {
                            $q2->orderBy('orden', 'asc');
                        },
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
        return DB::table('consecutivos_recibos_pedidos')
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
    }

    public function getParcialesPrenda(int $pedidoId, int $prendaId): Collection
    {
        return DB::table('pedidos_parciales')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_pedido_id', $prendaId)
            ->whereNull('deleted_at')
            ->select(['id', 'tipo_recibo', 'consecutivo_actual', 'consecutivo_inicial', 'activo', 'estado', 'created_at'])
            ->get();
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
