<?php

namespace App\Application\SupervisorPedidos\DTOs;

class UpdateOrderRequest
{
    private int $orderId;
    private string $cliente;
    private ?string $formaDePago;
    private ?string $novedades;
    private ?int $diaDeEntrega;
    private ?string $fechaEstimadaDeEntrega;
    private array $prendas;

    public function __construct(
        int $orderId,
        string $cliente,
        ?string $formaDePago = null,
        ?string $novedades = null,
        ?int $diaDeEntrega = null,
        ?string $fechaEstimadaDeEntrega = null,
        array $prendas = []
    ) {
        $this->orderId = $orderId;
        $this->cliente = $cliente;
        $this->formaDePago = $formaDePago;
        $this->novedades = $novedades;
        $this->diaDeEntrega = $diaDeEntrega;
        $this->fechaEstimadaDeEntrega = $fechaEstimadaDeEntrega;
        $this->prendas = $prendas;
    }

    public function getOrderId(): int { return $this->orderId; }
    public function getCliente(): string { return $this->cliente; }
    public function getFormaDePago(): ?string { return $this->formaDePago; }
    public function getNovedades(): ?string { return $this->novedades; }
    public function getDiaDeEntrega(): ?int { return $this->diaDeEntrega; }
    public function getFechaEstimadaDeEntrega(): ?string { return $this->fechaEstimadaDeEntrega; }
    public function getPrendas(): array { return $this->prendas; }
}
