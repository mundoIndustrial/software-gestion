<?php

namespace App\Application\Pedidos\DTOs;

/**
 * ObtenerDetalleCompletoResponse
 * 
 * Response Object que encapsula los datos completos del pedido
 * para recibos con todos los filtros y enriquecimientos aplicados.
 * 
 * Utilizadas en plantillas de recibos, bodega y gestión de insumos.
 */
class ObtenerDetalleCompletoResponse
{
    private array $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    /**
     * Retorna los datos completos como array
     */
    public function toArray(): array
    {
        return $this->datos;
    }

    /**
     * Obtiene un valor por clave
     */
    public function get(string $key, $default = null)
    {
        return $this->datos[$key] ?? $default;
    }

    /**
     * Retorna las prendas enriquecidas
     */
    public function getPrendas(): array
    {
        return $this->datos['prendas'] ?? [];
    }

    /**
     * Retorna ancho/metraje general
     */
    public function getAnchoMetrajeGeneral(): ?array
    {
        return $this->datos['ancho_metraje'] ?? null;
    }

    /**
     * Retorna fecha estimada de entrega
     */
    public function getFechaEstimadaEntrega()
    {
        return $this->datos['fecha_estimada_de_entrega'] ?? null;
    }

    /**
     * Retorna área
     */
    public function getArea()
    {
        return $this->datos['area'] ?? null;
    }

    /**
     * Retorna día de entrega
     */
    public function getDiaEntrega()
    {
        return $this->datos['dia_de_entrega'] ?? null;
    }
}
