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
