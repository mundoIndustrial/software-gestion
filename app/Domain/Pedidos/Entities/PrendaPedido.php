<?php

namespace App\Domain\Pedidos\Entities;

use App\Domain\Shared\Entity;

/**
 * Entidad: PrendaPedido
 * 
 * Una prenda dentro de un pedido
 * Vive dentro del agregado Pedido (no es agregado raÃ­z)
 */
class PrendaPedido extends Entity
{
    private int $pedidoId;
    private int $prendaId;
    private string $descripcion;
    private int $cantidad;
    private ?string $observaciones;
    private array $tallas;

    public function __construct(
        ?int $id,
        int $pedidoId,
        int $prendaId,
        string $descripcion,
        int $cantidad,
        array $tallas,
        ?string $observaciones = null
    ) {
        parent::__construct($id);
        $this->validar($cantidad, $tallas);
        $this->pedidoId = $pedidoId;
        $this->prendaId = $prendaId;
        $this->descripcion = $descripcion;
        $this->cantidad = $cantidad;
        $this->tallas = $tallas;
        $this->observaciones = $observaciones;
    }

    private function validar(int $cantidad, array $tallas): void
    {
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('Cantidad debe ser mayor a 0');
        }

        $totalTallas = array_sum(
            array_map(fn($generos) => array_sum($generos), $tallas)
        );

        if ($totalTallas !== $cantidad) {
            throw new \InvalidArgumentException(
                "Total de tallas ($totalTallas) no coincide con cantidad ($cantidad)"
            );
        }
    }

    public function pedidoId(): int { return $this->pedidoId; }
    public function prendaId(): int { return $this->prendaId; }
    public function descripcion(): string { return $this->descripcion; }
    public function cantidad(): int { return $this->cantidad; }
    public function tallas(): array { return $this->tallas; }
    public function observaciones(): ?string { return $this->observaciones; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'pedido_id' => $this->pedidoId,
            'prenda_id' => $this->prendaId,
            'descripcion' => $this->descripcion,
            'cantidad' => $this->cantidad,
            'tallas' => json_encode($this->tallas),
            'observaciones' => $this->observaciones,
        ];
    }
}

