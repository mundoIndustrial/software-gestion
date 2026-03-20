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
