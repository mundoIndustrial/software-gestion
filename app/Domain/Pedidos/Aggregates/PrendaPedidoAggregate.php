<?php

namespace App\Domain\Pedidos\Aggregates;

use App\Domain\Shared\DomainEvent;
use App\Domain\Pedidos\Events\PrendaPedidoAgregada;

/**
 * PrendaPedidoAggregate
 * 
 * RaÃ­z de agregado para Prenda de Pedido
 * Encapsula:
 * - Todos los datos de una prenda dentro de un pedido
 * - Variantes (color, tela, manga, broche)
 * - Cantidades por talla
 * - Invariantes de negocio
 * 
 * Una prenda es una entidad dentro del agregado del pedido.
 * Aunque tiene su propio agregado, siempre se accede a travÃ©s del pedido.
 */
class PrendaPedidoAggregate
{
    /**
     * Identificador Ãºnico de la prenda
     */
    private int|string $id;

    /**
     * Referencia al pedido al que pertenece
     */
    private int|string $pedidoId;

    /**
     * Nombre comercial de la prenda
     */
    private string $nombrePrenda;

    /**
     * Cantidad total de prendas
     */
    private int $cantidad;

    /**
     * GÃ©nero de la prenda
     */
    private string $genero;

    /**
     * IDs de variantes
     */
    private ?int $colorId;
    private ?int $telaId;
    private ?int $tipoMangaId;
    private ?int $tipoBrocheId;

    /**
     * Cantidades por talla (estructura flexible)
     */
    private array $cantidadPorTalla = [];

    /**
     * Eventos de dominio pendientes
     * 
     * @var array<DomainEvent>
     */
    private array $uncommittedEvents = [];

    private function __construct(
        int|string $id,
        int|string $pedidoId,
        string $nombrePrenda,
        int $cantidad,
        string $genero,
    ) {
        $this->id = $id;
        $this->pedidoId = $pedidoId;
        $this->nombrePrenda = $nombrePrenda;
        $this->cantidad = $cantidad;
        $this->genero = $genero;
    }

    /**
     * Factory method: Crear nueva prenda para un pedido
     */
    public static function crear(
        int|string $id,
        int|string $pedidoId,
        string $nombrePrenda,
        int $cantidad,
        string $genero,
    ): self {
        // Validar invariantes
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0');
        }

        if (empty($nombrePrenda)) {
            throw new \InvalidArgumentException('El nombre de la prenda es requerido');
        }

        $agregado = new self($id, $pedidoId, $nombrePrenda, $cantidad, $genero);

        // Registrar evento de creación
        $agregado->recordEvent(
            new PrendaPedidoAgregada(
                $pedidoId,
                $id,
                $nombrePrenda,
                $cantidad,
                $genero,
            )
        );

        return $agregado;
    }

    /**
     * Agregar variantes a la prenda
     */
    public function agregarVariantes(
        ?int $colorId = null,
        ?int $telaId = null,
        ?int $tipoMangaId = null,
        ?int $tipoBrocheId = null,
    ): void {
        $this->colorId = $colorId;
        $this->telaId = $telaId;
        $this->tipoMangaId = $tipoMangaId;
        $this->tipoBrocheId = $tipoBrocheId;
    }

    /**
     * Establecer cantidades por talla
     */
    public function establecerCantidadPorTalla(array $cantidades): void
    {
        // Validar que la suma de cantidades coincida con cantidad total
        $total = 0;
        foreach ($cantidades as $value) {
            if (is_array($value)) {
                $total += array_sum($value);
            } else {
                $total += (int)$value;
            }
        }

        if ($total !== $this->cantidad) {
            throw new \InvalidArgumentException(
                "La suma de cantidades por talla ($total) no coincide con cantidad total ({$this->cantidad})"
            );
        }

        $this->cantidadPorTalla = $cantidades;
    }

    /**
     * Registrar evento en el agregado
     */
    public function recordEvent(DomainEvent $event): void
    {
        $this->uncommittedEvents[] = $event;
    }

    /**
     * Obtener eventos no publicados
     * 
     * @return array<DomainEvent>
     */
    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    /**
     * Marcar eventos como publicados
     */
    public function markEventsAsCommitted(): void
    {
        $this->uncommittedEvents = [];
    }

    // Getters
    public function getId(): int|string
    {
        return $this->id;
    }

    public function getPedidoId(): int|string
    {
        return $this->pedidoId;
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

    public function getCantidadPorTalla(): array
    {
        return $this->cantidadPorTalla;
    }
}

