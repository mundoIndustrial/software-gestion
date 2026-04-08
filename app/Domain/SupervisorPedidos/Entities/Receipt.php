<?php

namespace App\Domain\SupervisorPedidos\Entities;

use App\Domain\SupervisorPedidos\ValueObjects\OrderId;
use App\Domain\SupervisorPedidos\ValueObjects\PrendaId;
use App\Domain\SupervisorPedidos\ValueObjects\ReceiptType;
use Carbon\Carbon;

class Receipt
{
    private int $id;
    private OrderId $orderId;
    private PrendaId $prendaId;
    private ReceiptType $type;
    private string $receiptNumber;
    private bool $isActive;
    private ?\DateTime $approvedAt;
    private ?string $sewingColor;

    public function __construct(
        int $id,
        OrderId $orderId,
        PrendaId $prendaId,
        ReceiptType $type,
        string $receiptNumber,
        bool $isActive = true,
        ?\DateTime $approvedAt = null,
        ?string $sewingColor = null
    ) {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->prendaId = $prendaId;
        $this->type = $type;
        $this->receiptNumber = $receiptNumber;
        $this->isActive = $isActive;
        $this->approvedAt = $approvedAt;
        $this->sewingColor = $sewingColor;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOrderId(): OrderId
    {
        return $this->orderId;
    }

    public function getPrendaId(): PrendaId
    {
        return $this->prendaId;
    }

    public function getType(): ReceiptType
    {
        return $this->type;
    }

    public function getReceiptNumber(): string
    {
        return $this->receiptNumber;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getApprovedAt(): ?\DateTime
    {
        return $this->approvedAt;
    }

    public function getSewingColor(): ?string
    {
        return $this->sewingColor;
    }

    public function approve(): void
    {
        if ($this->isActive) {
            throw new \DomainException('El recibo ya está aprobado');
        }

        $this->isActive = true;
        $this->approvedAt = Carbon::now();
    }

    public function cancel(): void
    {
        if (!$this->isActive) {
            throw new \DomainException('El recibo ya está cancelado');
        }

        $this->isActive = false;
    }

    public function setSewingColor(string $color): void
    {
        if (!$this->type->isSewing()) {
            throw new \DomainException('Solo se puede asignar color a recibos de costura');
        }

        $this->sewingColor = $color;
    }
}
