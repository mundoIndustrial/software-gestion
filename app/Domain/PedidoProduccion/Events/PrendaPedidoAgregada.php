<?php

namespace App\Domain\PedidoProduccion\Events;

use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

/**
 * PrendaPedidoAgregada
 * 
 * Se emite cuando se agrega una nueva prenda a un pedido
 * Contiene detalles de la prenda y su configuración
 */
class PrendaPedidoAgregada extends DomainEvent
{
    public function __construct(
        private int|string $pedidoId,
        private int|string $prendaId,
        private string $nombrePrenda,
        private int $cantidad,
        private string $genero,
        private ?int $colorId = null,
        private ?int $telaId = null,
        private ?int $tipoMangaId = null,
        private ?int $tipoBrocheId = null,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        parent::__construct($pedidoId, $occurredAt);
    }

    public function getPedidoId(): int|string
    {
        return $this->pedidoId;
    }

    public function getPrendaId(): int|string
    {
        return $this->prendaId;
    }

    public function getNombrePrenda(): string
    {
        return $this->nombrePrenda;
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    public function getGenero(): string
    {
        return $this->genero;
    }

    public function getColorId(): ?int
    {
        return $this->colorId;
    }

    public function getTelaId(): ?int
    {
        return $this->telaId;
    }

    public function getTipoMangaId(): ?int
    {
        return $this->tipoMangaId;
    }

    public function getTipoBrocheId(): ?int
    {
        return $this->tipoBrocheId;
    }

    protected function extractEventData(): array
    {
        return [
            'pedido_id' => $this->getPedidoId(),
            'prenda_id' => $this->getPrendaId(),
            'nombre_prenda' => $this->getNombrePrenda(),
            'cantidad' => $this->getCantidad(),
            'genero' => $this->getGenero(),
            'color_id' => $this->getColorId(),
            'tela_id' => $this->getTelaId(),
            'tipo_manga_id' => $this->getTipoMangaId(),
            'tipo_broche_id' => $this->getTipoBrocheId(),
        ];
    }
}
