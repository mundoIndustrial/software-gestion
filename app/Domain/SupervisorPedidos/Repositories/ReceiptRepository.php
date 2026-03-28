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

    /**
     * @return array<int, object>
     */
    public function findPendingEmbroideryStampingReceipts(array $receiptTypes): array;

    /**
     * @param array<int, int|string> $prendaIds
     * @return array<int|string, int>
     */
    public function sumQuantitiesByPrendaIds(array $prendaIds): array;

    /**
     * @param array<int, int|string> $partialIds
     * @return array<int|string, int>
     */
    public function sumQuantitiesByPartialIds(array $partialIds): array;

    /**
     * @param array<string, mixed> $filters
     * @return array<int, object>
     */
    public function findPendingSewingReceipts(array $filters): array;

    /**
     * @param array<string, mixed> $filters
     * @return array<int, object>
     */
    public function findPendingQualityControlReceipts(array $filters): array;

    /**
     * @return array<int, object>
     */
    public function findGarmentsWithColorsByPrendaId(int $prendaId): array;

    /**
     * @return array<int, object>
     */
    public function findGarmentsWithoutColorsByPrendaId(int $prendaId): array;

    public function getSewingReceiptFilterOptions(string $field): array;

    public function getQualityControlReceiptFilterOptions(string $field): array;

    public function generateNextConsecutiveForType(string $receiptType): string;
}
