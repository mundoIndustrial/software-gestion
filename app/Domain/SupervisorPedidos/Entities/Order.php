<?php

namespace App\Domain\SupervisorPedidos\Entities;

use App\Domain\SupervisorPedidos\ValueObjects\OrderId;
use App\Domain\SupervisorPedidos\ValueObjects\OrderStatus;
use App\Domain\SupervisorPedidos\DomainEvents\OrderApprovedEvent;
use App\Domain\SupervisorPedidos\DomainEvents\OrderReturnedEvent;
use App\Domain\SupervisorPedidos\DomainEvents\OrderCancelledEvent;
use Carbon\Carbon;

class Order
{
    private OrderId $id;
    private OrderStatus $status;
    private string $customerName;
    private string $orderNumber;
    private ?\DateTime $approvedBySupervisorAt;
    private string $notes;
    private array $domainEvents = [];

    public function __construct(
        OrderId $id,
        OrderStatus $status,
        string $customerName,
        string $orderNumber,
        ?\DateTime $approvedBySupervisorAt = null,
        string $notes = ''
    ) {
        $this->id = $id;
        $this->status = $status;
        $this->customerName = $customerName;
        $this->orderNumber = $orderNumber;
        $this->approvedBySupervisorAt = $approvedBySupervisorAt;
        $this->notes = $notes;
    }

    public function getId(): OrderId
    {
        return $this->id;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }

    public function getApprovedBySupervisorAt(): ?\DateTime
    {
        return $this->approvedBySupervisorAt;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isApproved(): bool
    {
        return $this->status->isApproved();
    }

    public function approve(): void
    {
        if (!$this->status->isPending()) {
            throw new \DomainException('Solo se pueden aprobar órdenes pendientes');
        }

        // Persistimos el valor de estado canonical del dominio/BD
        $this->status = new OrderStatus('PENDIENTE_INSUMOS');
        $this->approvedBySupervisorAt = Carbon::now();

        $this->recordDomainEvent(new OrderApprovedEvent($this->id));
    }

    public function returnToAdvisor(string $reason): void
    {
        if ($this->status->isPending()) {
            throw new \DomainException('No se puede devolver una orden pendiente');
        }

        $this->status = new OrderStatus('DEVUELTO_A_ASESORA');
        $this->approvedBySupervisorAt = Carbon::now();
        $this->notes = $reason;

        $this->recordDomainEvent(new OrderReturnedEvent($this->id, $reason));
    }

    public function cancel(): void
    {
        if ($this->status->isCancelled()) {
            throw new \DomainException('La orden ya está cancelada');
        }

        $this->status = new OrderStatus('Anulada');
        $this->recordDomainEvent(new OrderCancelledEvent($this->id));
    }

    public function addNote(string $note): void
    {
        $this->notes .= "\n\n" . $note;
    }

    protected function recordDomainEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
