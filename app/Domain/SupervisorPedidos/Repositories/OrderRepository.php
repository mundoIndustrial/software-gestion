<?php

namespace App\Domain\SupervisorPedidos\Repositories;

use App\Domain\SupervisorPedidos\Entities\Order;
use App\Domain\SupervisorPedidos\ValueObjects\OrderId;

interface OrderRepository
{
    public function findById(OrderId $id): ?Order;

    public function save(Order $order): void;

    public function findAllPending(): array;

    public function findByOrderNumber(string $orderNumber): ?Order;

    public function findByIdWithRelations(int $id): ?array;

    public function updateMultiple(int $id, array $data): ?array;

    public function updateStatus(int $id, string $status): ?array;
}
