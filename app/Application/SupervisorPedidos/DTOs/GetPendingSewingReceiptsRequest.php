<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetPendingSewingReceiptsRequest
{
    private ?string $numeroRecibo;
    private ?string $cliente;
    private ?string $asesor;
    private ?string $prendas;
    private ?string $fechaCreacion;
    private ?string $busqueda;

    public function __construct(
        ?string $numeroRecibo = null,
        ?string $cliente = null,
        ?string $asesor = null,
        ?string $prendas = null,
        ?string $fechaCreacion = null,
        ?string $busqueda = null
    ) {
        $this->numeroRecibo = $numeroRecibo;
        $this->cliente = $cliente;
        $this->asesor = $asesor;
        $this->prendas = $prendas;
        $this->fechaCreacion = $fechaCreacion;
        $busqueda = trim((string) ($busqueda ?? ''));
        $this->busqueda = $busqueda !== '' ? $busqueda : null;
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

    public function getBusqueda(): ?string
    {
        return $this->busqueda;
    }
}
