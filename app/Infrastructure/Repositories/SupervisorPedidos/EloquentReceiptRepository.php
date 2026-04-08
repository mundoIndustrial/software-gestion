<?php

namespace App\Infrastructure\Repositories\SupervisorPedidos;

use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;
use App\Domain\SupervisorPedidos\Entities\Receipt;
use App\Domain\SupervisorPedidos\ValueObjects\OrderId;
use App\Domain\SupervisorPedidos\ValueObjects\PrendaId;
use App\Domain\SupervisorPedidos\ValueObjects\ReceiptType;
use Illuminate\Support\Facades\DB;

class EloquentReceiptRepository implements ReceiptRepository
{
    public function findById(int $id): ?Receipt
    {
        $receipt = DB::table('consecutivos_recibos_pedidos')->find($id);

        if (!$receipt) {
            return null;
        }

        return $this->toDomain($receipt);
    }

    public function findByOrderAndPrenda(OrderId $orderId, PrendaId $prendaId, ReceiptType $type): ?Receipt
    {
        $receipt = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $orderId->value())
            ->where('prenda_id', $prendaId->value())
            ->where('tipo_recibo', $type->value())
            ->orderByDesc('id')
            ->first();

        if (!$receipt) {
            return null;
        }

        return $this->toDomain($receipt);
    }

    public function save(Receipt $receipt): void
    {
        DB::table('consecutivos_recibos_pedidos')
            ->where('id', $receipt->getId())
            ->update([
                'activo' => $receipt->isActive() ? 1 : 0,
                'color_costura' => $receipt->getSewingColor(),
                'updated_at' => now(),
            ]);
    }

    public function findActiveReceiptsByOrder(OrderId $orderId): array
    {
        $receipts = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $orderId->value())
            ->where('activo', 1)
            ->get();

        return $receipts->map(fn($r) => $this->toDomain($r))->toArray();
    }

    public function findByType(ReceiptType $type): array
    {
        $receipts = DB::table('consecutivos_recibos_pedidos')
            ->where('tipo_recibo', $type->value())
            ->where('activo', 1)
            ->get();

        return $receipts->map(fn($r) => $this->toDomain($r))->toArray();
    }

    public function findActiveBySewingType(int $orderId, int $prendaId): ?array
    {
        $receipt = DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $orderId)
            ->where('prenda_id', $prendaId)
            ->where('tipo_recibo', 'COSTURA')
            ->where('activo', 1)
            ->orderByDesc('id')
            ->first();

        if (!$receipt) {
            return null;
        }

        return (array) $receipt;
    }

    public function cancel(int $receiptId, ?string $notes = null): ?array
    {
        $receipt = DB::table('consecutivos_recibos_pedidos')->find($receiptId);

        if (!$receipt) {
            return null;
        }

        DB::table('consecutivos_recibos_pedidos')
            ->where('id', $receiptId)
            ->update([
                'activo' => 0,
                'observaciones' => $notes,
                'updated_at' => now(),
            ]);

        return (array) DB::table('consecutivos_recibos_pedidos')->find($receiptId);
    }

    public function saveArrivalDate(int $receiptId, ?string $arrivalDate): ?array
    {
        $receipt = DB::table('consecutivos_recibos_pedidos')->find($receiptId);

        if (!$receipt) {
            return null;
        }

        DB::table('consecutivos_recibos_pedidos')
            ->where('id', $receiptId)
            ->update([
                'fecha_llegada' => $arrivalDate,
                'updated_at' => now(),
            ]);

        return (array) DB::table('consecutivos_recibos_pedidos')->find($receiptId);
    }

    public function findByIdWithDetails(int $receiptId): ?array
    {
        $recibo = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('pedidos_produccion as p', 'crp.pedido_produccion_id', '=', 'p.id')
            ->join('users as u', 'p.asesor_id', '=', 'u.id')
            ->leftJoin('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
            ->leftJoin('pedidos_procesos_prenda_detalles as ppd', 'pp.id', '=', 'ppd.prenda_pedido_id')
            ->select([
                'crp.*',
                'p.cliente',
                'p.created_at as fecha_creacion',
                'u.name as asesor',
                'pp.nombre_prenda',
                'ppd.estado',
                'ppd.observaciones'
            ])
            ->where('crp.id', $receiptId)
            ->first();

        if (!$recibo) {
            return null;
        }

        $receiptArray = (array) $recibo;

        // Obtener tallas de la prenda
        $tallas = [];
        if ($receiptArray['prenda_id']) {
            $tallas = DB::table('prenda_pedido_tallas')
                ->where('prenda_pedido_id', $receiptArray['prenda_id'])
                ->get(['genero', 'talla', 'cantidad'])
                ->toArray();
        }

        // Obtener imágenes del proceso
        $imagenes = [];
        if ($receiptArray['prenda_id']) {
            $imagenes = DB::table('pedidos_procesos_imagenes')
                ->join('pedidos_procesos_prenda_detalles as ppd', 'pedidos_procesos_imagenes.proceso_prenda_detalle_id', '=', 'ppd.id')
                ->where('ppd.prenda_pedido_id', $receiptArray['prenda_id'])
                ->orderBy('pedidos_procesos_imagenes.orden')
                ->get(['pedidos_procesos_imagenes.ruta_original', 'pedidos_procesos_imagenes.ruta_webp'])
                ->toArray();
        }

        $receiptArray['tallas'] = $tallas;
        $receiptArray['imagenes'] = $imagenes;

        return $receiptArray;
    }

    public function approve(int $receiptId): ?array
    {
        $recibo = DB::table('consecutivos_recibos_pedidos')->find($receiptId);

        if (!$recibo) {
            return null;
        }

        // Actualizar el proceso asociado si existe
        $actualizado = 0;
        if ($recibo->prenda_id) {
            $actualizado = DB::table('pedidos_procesos_prenda_detalles')
                ->where('prenda_pedido_id', $recibo->prenda_id)
                ->where('estado', 'PENDIENTE')
                ->update([
                    'estado' => 'COMPLETADO',
                    'updated_at' => now()
                ]);
        }

        // Marcar el recibo como inactivo
        DB::table('consecutivos_recibos_pedidos')
            ->where('id', $receiptId)
            ->update([
                'activo' => 0,
                'updated_at' => now()
            ]);

        $updatedRecibo = DB::table('consecutivos_recibos_pedidos')->find($receiptId);
        $result = (array) $updatedRecibo;
        $result['procesos_actualizados'] = $actualizado;

        return $result;
    }

    public function findPendingEmbroideryStampingReceipts(array $receiptTypes): array
    {
        return DB::table('consecutivos_recibos_pedidos as crp')
            ->join('pedidos_produccion as p', 'crp.pedido_produccion_id', '=', 'p.id')
            ->join('users as u', 'p.asesor_id', '=', 'u.id')
            ->leftJoin('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
            ->leftJoin('recibos_fechas_llegada as rfl', 'rfl.recibo_id', '=', 'crp.id')
            ->leftJoin('pedidos_procesos_prenda_detalles as ppd', function ($join) {
                $join->on('pp.id', '=', 'ppd.prenda_pedido_id')
                    ->where(function ($q) {
                        $q->where('ppd.tipo_recibo', '=', DB::raw('crp.tipo_recibo'))
                            ->orWhere(function ($q2) {
                                $q2->whereNull('ppd.tipo_recibo')
                                    ->whereRaw("ppd.tipo_proceso_id = (CASE crp.tipo_recibo WHEN 'BORDADO' THEN 2 WHEN 'ESTAMPADO' THEN 3 WHEN 'DTF' THEN 4 WHEN 'SUBLIMADO' THEN 5 ELSE NULL END)");
                            });
                    })
                    ->where(function ($q) {
                        $q->whereNull('ppd.numero_recibo')
                            ->orWhere('ppd.numero_recibo', '=', DB::raw('crp.consecutivo_actual'));
                    });
            })
            ->select([
                'p.created_at as fecha_creacion',
                'crp.consecutivo_actual as numero_recibo',
                'p.cliente',
                'p.id as pedido_id',
                'u.name as asesor',
                'crp.tipo_recibo',
                'pp.nombre_prenda',
                'crp.id as recibo_id',
                'pp.id as prenda_id',
                'crp.notas as recibo_notas',
                'ppd.fecha_aprobacion',
                'rfl.fecha_llegada',
            ])
            ->whereIn('crp.tipo_recibo', $receiptTypes)
            ->where('crp.activo', 1)
            ->orderBy('p.created_at', 'desc')
            ->get()
            ->all();
    }

    public function sumQuantitiesByPrendaIds(array $prendaIds): array
    {
        if (empty($prendaIds)) {
            return [];
        }

        return DB::table('prenda_pedido_tallas')
            ->select('prenda_pedido_id', DB::raw('SUM(cantidad) as total'))
            ->whereIn('prenda_pedido_id', $prendaIds)
            ->groupBy('prenda_pedido_id')
            ->pluck('total', 'prenda_pedido_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    public function sumQuantitiesByPartialIds(array $partialIds): array
    {
        if (empty($partialIds)) {
            return [];
        }

        return DB::table('pedidos_parciales_tallas')
            ->select('pedido_parcial_id', DB::raw('SUM(cantidad) as total'))
            ->whereIn('pedido_parcial_id', $partialIds)
            ->groupBy('pedido_parcial_id')
            ->pluck('total', 'pedido_parcial_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    public function findPendingSewingReceipts(array $filters): array
    {
        $query = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('pedidos_produccion as p', 'crp.pedido_produccion_id', '=', 'p.id')
            ->join('users as u', 'p.asesor_id', '=', 'u.id')
            ->select([
                'p.created_at as fecha_creacion',
                'crp.consecutivo_actual as numero_recibo',
                'crp.prenda_id as prenda_id',
                'p.cliente',
                'p.id as pedido_id',
                'u.name as asesor',
                'crp.color_costura',
                'p.area',
            ])
            ->where('crp.tipo_recibo', 'COSTURA')
            ->where('crp.activo', 1)
            ->orderBy('p.created_at', 'desc');

        $this->applyPendingReceiptFilters($query, $filters);

        return $query->get()->all();
    }

    public function findPendingQualityControlReceipts(array $filters): array
    {
        $query = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('pedidos_produccion as p', 'crp.pedido_produccion_id', '=', 'p.id')
            ->leftJoin('users as u', 'p.asesor_id', '=', 'u.id')
            ->select([
                'p.created_at as fecha_creacion',
                'crp.consecutivo_actual as numero_recibo',
                'crp.prenda_id as prenda_id',
                'p.cliente',
                'p.id as pedido_id',
                'u.name as asesor',
                'crp.color_costura',
                'crp.area',
            ])
            ->where('crp.tipo_recibo', 'COSTURA')
            ->where('crp.activo', 1)
            ->whereRaw('LOWER(TRIM(crp.area)) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->orderBy('p.created_at', 'desc');

        $this->applyPendingReceiptFilters($query, $filters);

        return $query->get()->all();
    }

    public function findGarmentsWithColorsByPrendaId(int $prendaId): array
    {
        return DB::table('prendas_pedido as pp')
            ->join('prenda_pedido_tallas as ppt', 'pp.id', '=', 'ppt.prenda_pedido_id')
            ->join('prenda_pedido_talla_colores as pptc', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->select([
                'pp.nombre_prenda',
                'pptc.color_nombre',
                'pptc.cantidad as cantidad_color',
                DB::raw('null as cantidad_talla'),
                DB::raw('null as tela'),
            ])
            ->where('pp.id', $prendaId)
            ->get()
            ->all();
    }

    public function findGarmentsWithoutColorsByPrendaId(int $prendaId): array
    {
        return DB::table('prendas_pedido as pp')
            ->join('prenda_pedido_tallas as ppt', 'pp.id', '=', 'ppt.prenda_pedido_id')
            ->leftJoin('prenda_pedido_talla_colores as pptc', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
            ->select([
                'pp.nombre_prenda',
                'ppt.tela',
                'ppt.cantidad as cantidad_talla',
                DB::raw('null as color_nombre'),
                DB::raw('null as cantidad_color'),
            ])
            ->where('pp.id', $prendaId)
            ->whereNull('pptc.id')
            ->get()
            ->all();
    }

    public function getSewingReceiptFilterOptions(string $field): array
    {
        return match ($field) {
            'numero_recibo' => $this->buildSewingFiltersBaseQuery()
                ->distinct()
                ->pluck('crp.consecutivo_actual')
                ->filter()
                ->sort()
                ->values()
                ->toArray(),
            'cliente' => $this->buildSewingFiltersBaseQuery()
                ->distinct()
                ->pluck('p.cliente')
                ->filter()
                ->sort()
                ->values()
                ->toArray(),
            'area' => $this->buildSewingFiltersBaseQuery()
                ->distinct()
                ->pluck('p.area')
                ->filter()
                ->sort()
                ->values()
                ->toArray(),
            'asesor' => $this->buildSewingFiltersBaseQuery()
                ->distinct()
                ->pluck('u.name')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray(),
            'prendas' => $this->buildSewingFiltersBaseQuery()
                ->leftJoin('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
                ->distinct()
                ->pluck('pp.nombre_prenda')
                ->filter()
                ->sort()
                ->values()
                ->toArray(),
            default => [],
        };
    }

    public function getQualityControlReceiptFilterOptions(string $field): array
    {
        return match ($field) {
            'numero_recibo' => $this->buildQualityControlFiltersBaseQuery()
                ->distinct()
                ->pluck('pp.numero_recibo')
                ->filter()
                ->sort()
                ->values()
                ->toArray(),
            'cliente' => $this->buildQualityControlFiltersBaseQuery()
                ->distinct()
                ->pluck('p.cliente')
                ->filter()
                ->sort()
                ->values()
                ->toArray(),
            'area' => ['Control de Calidad'],
            'asesor' => $this->buildQualityControlFiltersBaseQuery()
                ->distinct()
                ->pluck('u.name')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->toArray(),
            'prendas' => $this->buildQualityControlFiltersBaseQuery()
                ->distinct()
                ->pluck('prenda.nombre_prenda')
                ->filter()
                ->sort()
                ->values()
                ->toArray(),
            default => [],
        };
    }

    public function generateNextConsecutiveForType(string $receiptType): string
    {
        $master = DB::table('consecutivos_recibos')
            ->where('tipo_recibo', $receiptType)
            ->where('activo', 1)
            ->lockForUpdate()
            ->first();

        if (!$master) {
            throw new \RuntimeException("No existe consecutivo maestro para {$receiptType}");
        }

        $newConsecutive = (int) $master->consecutivo_actual + 1;

        DB::table('consecutivos_recibos')
            ->where('id', $master->id)
            ->update([
                'consecutivo_actual' => $newConsecutive,
                'updated_at' => now(),
            ]);

        return (string) $newConsecutive;
    }

    public function updateSewingReceiptColor(string $receiptNumber, string $color): int
    {
        $normalizedReceiptNumber = trim((string) $receiptNumber);
        $normalizedColor = trim((string) $color);

        if ($normalizedReceiptNumber === '' || $normalizedColor === '') {
            return 0;
        }

        return DB::table('consecutivos_recibos_pedidos')
            ->where('consecutivo_actual', $normalizedReceiptNumber)
            ->whereIn('tipo_recibo', ['COSTURA', 'COSTURA-BODEGA', 'REFLECTIVO'])
            ->update([
                'color_costura' => $normalizedColor,
                'updated_at' => now(),
            ]);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyPendingReceiptFilters($query, array $filters): void
    {
        $numeroRecibo = $filters['numero_recibo'] ?? [];
        if (!empty($numeroRecibo)) {
            $query->whereIn('crp.consecutivo_actual', $numeroRecibo);
        }

        $cliente = $filters['cliente'] ?? [];
        if (!empty($cliente)) {
            $query->whereIn('p.cliente', $cliente);
        }

        $asesor = $filters['asesor'] ?? [];
        if (!empty($asesor)) {
            $query->whereIn('u.name', $asesor);
        }

        $prendas = $filters['prendas'] ?? [];
        if (!empty($prendas)) {
            $query->whereExists(function ($q) use ($prendas) {
                $q->select(DB::raw(1))
                    ->from('prendas_pedido as pp')
                    ->whereColumn('pp.id', 'crp.prenda_id')
                    ->whereIn('pp.nombre_prenda', $prendas);
            });
        }

        $fechaCreacion = $filters['fecha_creacion'] ?? null;
        if (!empty($fechaCreacion)) {
            $query->whereDate('p.created_at', $fechaCreacion);
        }
    }

    private function buildSewingFiltersBaseQuery()
    {
        return DB::table('consecutivos_recibos_pedidos as crp')
            ->join('pedidos_produccion as p', 'crp.pedido_produccion_id', '=', 'p.id')
            ->leftJoin('users as u', 'p.asesor_id', '=', 'u.id')
            ->where('crp.tipo_recibo', 'COSTURA')
            ->where('crp.activo', 1);
    }

    private function buildQualityControlFiltersBaseQuery()
    {
        return DB::table('procesos_prenda as pp')
            ->join('prendas_pedido as prenda', 'pp.prenda_pedido_id', '=', 'prenda.id')
            ->join('pedidos_produccion as p', 'pp.numero_pedido', '=', 'p.numero_pedido')
            ->leftJoin('users as u', 'p.asesor_id', '=', 'u.id')
            ->join('consecutivos_recibos_pedidos as crp', function ($join) {
                $join->on('crp.pedido_produccion_id', '=', 'p.id')
                    ->on('crp.consecutivo_actual', '=', 'pp.numero_recibo');
            })
            ->where('pp.proceso', 'Control de Calidad')
            ->where('pp.estado_proceso', 'Pendiente')
            ->where('crp.tipo_recibo', 'COSTURA')
            ->where('crp.activo', 1);
    }

    private function toDomain(object $model): Receipt
    {
        return new Receipt(
            $model->id,
            new OrderId($model->pedido_produccion_id),
            new PrendaId($model->prenda_id),
            new ReceiptType($model->tipo_recibo),
            $model->consecutivo_actual,
            (bool) ($model->activo ?? false),
            $model->updated_at ? \Carbon\Carbon::parse($model->updated_at) : null,
            $model->color_costura ?? null
        );
    }
}
