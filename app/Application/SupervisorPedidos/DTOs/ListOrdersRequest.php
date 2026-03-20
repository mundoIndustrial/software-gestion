<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ListOrdersRequest
{
    private ?string $mostrar = null;
    private ?string $aprobacion = null;
    private ?string $tipo = null;
    private ?string $busqueda = null;
    private ?string $numero = null;
    private ?string $cliente = null;
    private ?string $forma_pago = null;
    private ?string $estado = null;
    private ?string $asesora = null;
    private ?string $fecha_desde = null;
    private ?string $fecha_hasta = null;
    private int $page = 1;
    private int $perPage = 15;

    public function __construct(array $params = [])
    {
        $this->mostrar = $params['mostrar'] ?? null;
        $this->aprobacion = $params['aprobacion'] ?? null;
        $this->tipo = $params['tipo'] ?? null;
        $this->busqueda = $params['busqueda'] ?? null;
        $this->numero = $params['numero'] ?? null;
        $this->cliente = $params['cliente'] ?? null;
        $this->forma_pago = $params['forma_pago'] ?? null;
        $this->estado = $params['estado'] ?? null;
        $this->asesora = $params['asesora'] ?? null;
        $this->fecha_desde = $params['fecha_desde'] ?? null;
        $this->fecha_hasta = $params['fecha_hasta'] ?? null;
        $this->page = $params['page'] ?? 1;
        $this->perPage = $params['perPage'] ?? 15;
    }

    public function getMostrar(): ?string { return $this->mostrar; }
    public function getAprobacion(): ?string { return $this->aprobacion; }
    public function getTipo(): ?string { return $this->tipo; }
    public function getBusqueda(): ?string { return $this->busqueda; }
    public function getNumero(): ?string { return $this->numero; }
    public function getCliente(): ?string { return $this->cliente; }
    public function getFormaPago(): ?string { return $this->forma_pago; }
    public function getEstado(): ?string { return $this->estado; }
    public function getAsesora(): ?string { return $this->asesora; }
    public function getFechaDesde(): ?string { return $this->fecha_desde; }
    public function getFechaHasta(): ?string { return $this->fecha_hasta; }
    public function getPage(): int { return $this->page; }
    public function getPerPage(): int { return $this->perPage; }
}
