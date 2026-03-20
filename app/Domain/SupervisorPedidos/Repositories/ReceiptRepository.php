<?php

namespace App\Domain\SupervisorPedidos\Repositories;

use App\Domain\SupervisorPedidos\Entities\Receipt;
use App\Domain\SupervisorPedidos\ValueObjects\OrderId;
use App\Domain\SupervisorPedidos\ValueObjects\PrendaId;
use App\Domain\SupervisorPedidos\ValueObjects\ReceiptType;

interface ReceiptRepository
{
    public function findById(int $id): ?Receipt;

    public function findByOrderAndPrenda(OrderId $orderId, PrendaId $prendaId, ReceiptType $type): ?Receipt;

    public function save(Receipt $receipt): void;

    public function findActiveReceiptsByOrder(OrderId $orderId): array;

    public function findByType(ReceiptType $type): array;

    public function findActiveBySewingType(int $orderId, int $prendaId): ?array;

    public function cancel(int $receiptId, ?string $notes = null): ?array;

    public function saveArrivalDate(int $receiptId, ?string $arrivalDate): ?array;

    public function findByIdWithDetails(int $receiptId): ?array;

    public function approve(int $receiptId): ?array;
}
