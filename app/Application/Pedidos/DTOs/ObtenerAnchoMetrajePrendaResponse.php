<?php

namespace App\Application\Pedidos\DTOs;

/**
 * ObtenerAnchoMetrajePrendaResponse
 * 
 * DTO que encapsula la respuesta del caso de uso de obtener ancho/metraje de una prenda.
 */
class ObtenerAnchoMetrajePrendaResponse
{
    public function __construct(
        private ?float $ancho,
        private ?float $metraje,
        private ?string $contenidoMano,
        private ?string $tipoModo,
        private array $data = []
    ) {}

    public function toArray(): array
    {
        return [
            'success' => true,
            'ancho' => $this->ancho,
            'metraje' => $this->metraje,
            'contenido_mano' => $this->contenidoMano,
            'tipo_modo' => $this->tipoModo,
            'data' => $this->data
        ];
    }

    public function getAncho(): ?float
    {
        return $this->ancho;
    }

    public function getMetraje(): ?float
    {
        return $this->metraje;
    }

    public function getContenidoMano(): ?float
    {
        return $this->contenidoMano;
    }

    public function getTipoModo(): ?string
    {
        return $this->tipoModo;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
