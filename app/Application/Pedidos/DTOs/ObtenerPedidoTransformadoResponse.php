<?php

namespace App\Application\Pedidos\DTOs;

/**
 * ObtenerPedidoTransformadoResponse
 * 
 * Response Object para el UseCase que obtiene y transforma un pedido
 * con todo el enriquecimiento: tallas, EPPs, recibos parciales, etc.
 * 
 * Encapsula la lógica de serialización para el frontend.
 */
class ObtenerPedidoTransformadoResponse
{
    private array $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    /**
     * Retorna los datos como array para serialización JSON
     */
    public function toArray(): array
    {
        return $this->datos;
    }

    /**
     * Obtiene un valor específico del pedido transformado
     */
    public function get(string $key, $default = null)
    {
        return $this->datos[$key] ?? $default;
    }

    /**
     * Verifica si existe una clave
     */
    public function has(string $key): bool
    {
        return isset($this->datos[$key]);
    }

    /**
     * Retorna las prendas transformadas
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
     * Retorna la fecha de creación
     */
    public function getFechaCreacion(): ?string
    {
        return $this->datos['fecha_creacion'] ?? null;
    }
}
