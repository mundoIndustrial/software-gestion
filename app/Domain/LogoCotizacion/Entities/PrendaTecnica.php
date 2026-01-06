<?php

namespace App\Domain\LogoCotizacion\Entities;

use App\Domain\LogoCotizacion\ValueObjects\TipoTecnica;
use App\Domain\LogoCotizacion\ValueObjects\UbicacionPrenda;
use App\Domain\LogoCotizacion\ValueObjects\Talla;
use DateTime;

/**
 * PrendaTecnica - Entity que representa una prenda dentro de una técnica
 * 
 * Una técnica (Bordado, Estampado, etc) puede tener múltiples prendas
 * con sus ubicaciones, tallas y cantidades específicas
 */
final class PrendaTecnica
{
    private int $id;
    private string $nombrePrenda;
    private string $descripcion;
    /** @var UbicacionPrenda[] */
    private array $ubicaciones;
    /** @var Talla[] */
    private array $tallas;
    private int $cantidad;
    private ?string $especificaciones;
    private ?string $colorHilo;
    private ?int $puntosEstimados;
    private bool $activo;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        int $id,
        string $nombrePrenda,
        string $descripcion,
        array $ubicaciones,
        ?array $tallas = null,
        int $cantidad = 1,
        ?string $especificaciones = null,
        ?string $colorHilo = null,
        ?int $puntosEstimados = null
    ) {
        $this->validar($nombrePrenda, $descripcion, $ubicaciones);

        $this->id = $id;
        $this->nombrePrenda = trim($nombrePrenda);
        $this->descripcion = trim($descripcion);
        $this->ubicaciones = $ubicaciones;
        $this->tallas = $tallas ?? [];
        $this->cantidad = max(1, $cantidad);
        $this->especificaciones = $especificaciones;
        $this->colorHilo = $colorHilo;
        $this->puntosEstimados = $puntosEstimados;
        $this->activo = true;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    private function validar(string $nombrePrenda, string $descripcion, array $ubicaciones): void
    {
        if (empty(trim($nombrePrenda))) {
            throw new \InvalidArgumentException('El nombre de la prenda no puede estar vacío');
        }
        if (empty(trim($descripcion))) {
            throw new \InvalidArgumentException('La descripción no puede estar vacía');
        }
        if (empty($ubicaciones)) {
            throw new \InvalidArgumentException('Debe especificar al menos una ubicación');
        }
    }

    public static function crear(
        string $nombrePrenda,
        string $descripcion,
        array $ubicaciones,
        ?array $tallas = null,
        int $cantidad = 1
    ): self {
        return new self(0, $nombrePrenda, $descripcion, $ubicaciones, $tallas, $cantidad);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function nombrePrenda(): string
    {
        return $this->nombrePrenda;
    }

    public function descripcion(): string
    {
        return $this->descripcion;
    }

    /**
     * @return UbicacionPrenda[]
     */
    public function ubicaciones(): array
    {
        return $this->ubicaciones;
    }

    /**
     * @return Talla[]
     */
    public function tallas(): array
    {
        return $this->tallas;
    }

    public function cantidad(): int
    {
        return $this->cantidad;
    }

    public function especificaciones(): ?string
    {
        return $this->especificaciones;
    }

    public function colorHilo(): ?string
    {
        return $this->colorHilo;
    }

    public function puntosEstimados(): ?int
    {
        return $this->puntosEstimados;
    }

    public function esActiva(): bool
    {
        return $this->activo;
    }

    public function activar(): void
    {
        $this->activo = true;
        $this->updatedAt = new DateTime();
    }

    public function desactivar(): void
    {
        $this->activo = false;
        $this->updatedAt = new DateTime();
    }

    public function actualizarCantidad(int $cantidad): void
    {
        $this->cantidad = max(1, $cantidad);
        $this->updatedAt = new DateTime();
    }

    public function actualizarUbicaciones(array $ubicaciones): void
    {
        if (empty($ubicaciones)) {
            throw new \InvalidArgumentException('Debe especificar al menos una ubicación');
        }
        $this->ubicaciones = $ubicaciones;
        $this->updatedAt = new DateTime();
    }
}
