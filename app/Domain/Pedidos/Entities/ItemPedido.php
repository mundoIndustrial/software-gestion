<?php

namespace App\Domain\Pedidos\Entities;

use App\Domain\Shared\Entity;
use App\Domain\Pedidos\ValueObjects\TipoItem;
use App\Domain\Pedidos\ValueObjects\OrdenItem;

/**
 * Entity: ItemPedido
 * 
 * Representa un item (Prenda o EPP) dentro de un Pedido
 * 
 * Invariantes:
 * - Debe tener un ID de referencia (prendaId o eppId)
 * - Debe tener un tipo (prenda o epp)
 * - Debe tener una posición en el orden
 * - Debe tener datos para renderización en cliente
 */
class ItemPedido extends Entity
{
    private int $pedidoId;
    private int $referenciaId;  // ID de la Prenda o EPP
    private TipoItem $tipo;
    private OrdenItem $orden;
    private string $nombre;
    private ?string $descripcion;
    private array $datosPresentacion;  // Datos necesarios para renderizar en frontend
    private \DateTime $fechaCreacion;

    public function __construct(
        ?int $id,
        int $pedidoId,
        int $referenciaId,
        TipoItem $tipo,
        OrdenItem $orden,
        string $nombre,
        ?string $descripcion,
        array $datosPresentacion,
        ?\DateTime $fechaCreacion = null
    ) {
        parent::__construct($id);
        
        if ($referenciaId <= 0) {
            throw new \InvalidArgumentException('ID de referencia inválido');
        }

        if (empty($nombre)) {
            throw new \InvalidArgumentException('El nombre del item es requerido');
        }

        $this->pedidoId = $pedidoId;
        $this->referenciaId = $referenciaId;
        $this->tipo = $tipo;
        $this->orden = $orden;
        $this->nombre = $nombre;
        $this->descripcion = $descripcion;
        $this->datosPresentacion = $datosPresentacion;
        $this->fechaCreacion = $fechaCreacion ?? new \DateTime();
    }

    public static function crearPrenda(
        int $pedidoId,
        int $prendaId,
        string $nombre,
        ?string $descripcion,
        OrdenItem $orden,
        array $datosPresentacion
    ): self {
        return new self(
            id: null,
            pedidoId: $pedidoId,
            referenciaId: $prendaId,
            tipo: TipoItem::prenda(),
            orden: $orden,
            nombre: $nombre,
            descripcion: $descripcion,
            datosPresentacion: $datosPresentacion,
            fechaCreacion: null
        );
    }

    public static function crearEpp(
        int $pedidoId,
        int $eppId,
        string $nombre,
        ?string $descripcion,
        OrdenItem $orden,
        array $datosPresentacion
    ): self {
        return new self(
            id: null,
            pedidoId: $pedidoId,
            referenciaId: $eppId,
            tipo: TipoItem::epp(),
            orden: $orden,
            nombre: $nombre,
            descripcion: $descripcion,
            datosPresentacion: $datosPresentacion,
            fechaCreacion: null
        );
    }

    public function cambiarOrden(OrdenItem $nuevoOrden): void
    {
        $this->orden = $nuevoOrden;
    }

    public function pedidoId(): int
    {
        return $this->pedidoId;
    }

    public function referenciaId(): int
    {
        return $this->referenciaId;
    }

    public function tipo(): TipoItem
    {
        return $this->tipo;
    }

    public function orden(): OrdenItem
    {
        return $this->orden;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function descripcion(): ?string
    {
        return $this->descripcion;
    }

    public function datosPresentacion(): array
    {
        return $this->datosPresentacion;
    }

    public function fechaCreacion(): \DateTime
    {
        return $this->fechaCreacion;
    }

    /**
     * Convertir a DTO para respuesta API
     * Esta es la única vez que convertimos a array para enviar al cliente
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo->valor(),
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'orden' => $this->orden->valor(),
            'referencia_id' => $this->referenciaId,
            'datos_presentacion' => $this->datosPresentacion,
        ];
    }
}
