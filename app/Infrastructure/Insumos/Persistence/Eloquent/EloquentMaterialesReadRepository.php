<?php

namespace App\Infrastructure\Insumos\Persistence\Eloquent;

use App\Domain\Insumos\Repositories\MaterialesReadRepository;
use App\Models\ConsecutivoReciboPedido;
use App\Models\MaterialesOrdenInsumos;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Repositories\Insumos\MaterialesRepository;
use Carbon\Carbon;

class EloquentMaterialesReadRepository implements MaterialesReadRepository
{
    public function __construct(
        private readonly MaterialesRepository $materialesRepository
    ) {
    }

    public function obtenerMaterialesPedido(
        string $numeroPedido,
        ?int $prendaId = null,
        ?int $prendaBodegaId = null,
        ?int $numeroRecibo = null,
        ?string $tipoRecibo = null
    ): array
    {
        PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();

        $query = MaterialesOrdenInsumos::where('numero_pedido', $numeroPedido);

        $tipoReciboNormalizado = strtoupper(trim((string) $tipoRecibo));
        $esBodega = $tipoReciboNormalizado === 'CORTE-PARA-BODEGA' || ($prendaBodegaId ?? 0) > 0;

        if ($esBodega && ($prendaBodegaId ?? 0) > 0) {
            $query->where('prenda_bodega_id', $prendaBodegaId);
            if (($numeroRecibo ?? 0) > 0) {
                $query->where('numero_recibo', $numeroRecibo);
            }
        } elseif ($prendaId) {
            $query->where('prenda_id', $prendaId);
        }

        $materiales = $query->get();
        $nombrePrenda = null;

        if ($prendaId) {
            $prenda = PrendaPedido::find($prendaId);
            $nombrePrenda = $prenda ? $prenda->nombre_prenda : null;
        }

        return [
            'success' => true,
            'materiales' => $materiales->map(function ($material) {
                return [
                    'id' => $material->id,
                    'nombre_material' => $material->nombre_material,
                    'recibido' => $material->recibido,
                    'prenda_id' => $material->prenda_id,
                    'fecha_orden' => $material->fecha_orden ? $material->fecha_orden->format('Y-m-d') : null,
                    'fecha_pedido' => $material->fecha_pedido ? $material->fecha_pedido->format('Y-m-d') : null,
                    'fecha_pago' => $material->fecha_pago ? $material->fecha_pago->format('Y-m-d') : null,
                    'fecha_llegada' => $material->fecha_llegada ? $material->fecha_llegada->format('Y-m-d') : null,
                    'fecha_despacho' => $material->fecha_despacho ? $material->fecha_despacho->format('Y-m-d') : null,
                    'dias_demora' => $material->dias_demora,
                    'observaciones' => $material->observaciones,
                ];
            }),
            'nombre_prenda' => $nombrePrenda,
        ];
    }

    public function marcarTodasNotificacionesLeidas(int $userId): array
    {
        $ordenesPendientes = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
            ->whereNotNull('cotizacion_id')
            ->where('estado', '!=', 'Anulada')
            ->pluck('id')
            ->toArray();

        session(['viewed_ordenes_' . $userId => $ordenesPendientes]);

        return [
            'success' => true,
            'message' => 'Notificaciones marcadas como leidas',
        ];
    }

    public function obtenerPrendasPedido(string $numeroPedido): array
    {
        $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();

        return [
            'success' => true,
            'prendas' => $pedido->prendas()
                ->select('id', 'nombre_prenda', 'descripcion')
                ->get(),
        ];
    }

    public function obtenerReciboPrenda(string $numeroPedido, int $prendaId): array
    {
        $pedido = PedidoProduccion::find($numeroPedido)
            ?? PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();

        $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
            ->where('prenda_id', $prendaId)
            ->where('activo', 1)
            ->orderBy('created_at', 'desc')
            ->first();

        return [
            'success' => $recibo !== null,
            'recibo' => $recibo?->consecutivo_actual,
        ];
    }

    public function obtenerOpcionesFiltro(string $column, string $tipoRecibo = 'COSTURA'): array
    {
        $columnasPermitidas = [
            'consecutivo_actual',
            'numero_pedido',
            'cliente',
            'estado',
            'area',
            'created_at',
        ];

        if (!in_array($column, $columnasPermitidas, true)) {
            throw new \InvalidArgumentException("Columna no permitida: {$column}");
        }

        $tipoReciboNormalizado = strtoupper(trim($tipoRecibo));

        if ($tipoReciboNormalizado === 'CORTE-PARA-BODEGA') {
            $query = ConsecutivoReciboPedido::query()
                ->leftJoin('prenda_bodega', 'consecutivos_recibos_pedidos.prenda_bodega_id', '=', 'prenda_bodega.id')
                ->whereRaw('UPPER(TRIM(consecutivos_recibos_pedidos.tipo_recibo)) = ?', ['CORTE-PARA-BODEGA'])
                ->where('consecutivos_recibos_pedidos.activo', 1);

            $campoSeleccion = match ($column) {
                'consecutivo_actual', 'numero_pedido' => 'consecutivos_recibos_pedidos.consecutivo_actual',
                'cliente' => 'prenda_bodega.descripcion',
                'estado' => 'consecutivos_recibos_pedidos.estado',
                'area' => 'consecutivos_recibos_pedidos.area',
                'created_at' => 'consecutivos_recibos_pedidos.created_at',
                default => null,
            };
        } else {
            $query = ConsecutivoReciboPedido::query()
                ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
                ->whereRaw('UPPER(TRIM(consecutivos_recibos_pedidos.tipo_recibo)) = ?', [$tipoReciboNormalizado])
                ->where('consecutivos_recibos_pedidos.activo', 1)
                ->where(function ($q) {
                    $q->where('pedidos_produccion.estado', 'PENDIENTE_INSUMOS')
                        ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR')
                        ->orWhere(function ($q2) {
                            $q2->where('pedidos_produccion.area', 'LIKE', '%Corte%')
                                ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR')
                                ->orWhere('pedidos_produccion.area', 'LIKE', '%Creacion%orden%')
                                ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR')
                                ->orWhere('pedidos_produccion.area', 'LIKE', '%Creacion de orden%')
                                ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR');
                        });
                });

            $campoSeleccion = match ($column) {
                'consecutivo_actual' => 'consecutivos_recibos_pedidos.consecutivo_actual',
                'numero_pedido' => 'pedidos_produccion.numero_pedido',
                'cliente' => 'pedidos_produccion.cliente',
                'estado' => 'consecutivos_recibos_pedidos.estado',
                'area' => 'pedidos_produccion.area',
                'created_at' => 'pedidos_produccion.created_at',
                default => null,
            };
        }

        if ($campoSeleccion === null) {
            return [];
        }

        $valores = $query
            ->whereNotNull($campoSeleccion)
            ->distinct()
            ->pluck($campoSeleccion)
            ->filter()
            ->values();

        if ($column === 'created_at') {
            return $valores
                ->map(function ($value) {
                    try {
                        return Carbon::parse($value)->format('d/m/Y');
                    } catch (\Throwable $e) {
                        return null;
                    }
                })
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        return $valores->map(fn($value) => (string) $value)->toArray();
    }
}
