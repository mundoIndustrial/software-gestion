<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetPendingSewingReceiptsRequest
{
    private ?string $numeroRecibo;
    private ?string $cliente;
    private ?string $asesor;
    private ?string $prendas;
    private ?string $fechaCreacion;

    public function __construct(
        ?string $numeroRecibo = null,
        ?string $cliente = null,
        ?string $asesor = null,
        ?string $prendas = null,
        ?string $fechaCreacion = null
    ) {
        $this->numeroRecibo = $numeroRecibo;
        $this->cliente = $cliente;
        $this->asesor = $asesor;
        $this->prendas = $prendas;
        $this->fechaCreacion = $fechaCreacion;
    }

    public function getNumeroRecibo(): ?string
    {
        return $this->numeroRecibo;
    }

    public function getCliente(): ?string
    {
        return $this->cliente;
    }

    public function getAsesor(): ?string
    {
        return $this->asesor;
    }

    public function getPrendas(): ?string
    {
        return $this->prendas;
    }

    public function getFechaCreacion(): ?string
    {
        return $this->fechaCreacion;
    }
}
