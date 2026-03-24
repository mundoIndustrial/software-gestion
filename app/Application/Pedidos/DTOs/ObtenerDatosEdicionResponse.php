<?php

namespace App\Application\Pedidos\DTOs;

/**
 * ObtenerDatosEdicionResponse
 * 
 * Response Object que encapsula los datos completos de un pedido
 * para formularios de edición.
 */
class ObtenerDatosEdicionResponse
{
    private array $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    /**
     * Retorna los datos como array
     */
    public function toArray(): array
    {
        return $this->datos;
    }

    /**
     * Obtiene un valor específico
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
     * Retorna los EPPs transformados
     */
    public function getEppsTransformados(): array
    {
        return $this->datos['epps_transformados'] ?? [];
    }

    /**
     * Retorna el asesor
     */
    public function getAsesor()
    {
        return $this->datos['asesor'] ?? null;
    }

    /**
     * Retorna el cliente
     */
    public function getCliente()
    {
        return $this->datos['cliente'] ?? null;
    }
}
