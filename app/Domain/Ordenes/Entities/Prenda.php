<?php

namespace App\Domain\Ordenes\Entities;

use Illuminate\Support\Collection;

/**
 * Entity: Prenda
 * 
 * Entidad dentro del agregado Orden.
 * No tiene existencia independiente, siempre pertenece a una Orden.
 */
class Prenda
{
    private string $nombrePrenda;
    private int $cantidadTotal;
    private float $cantidadEntregada;
    private array $cantidadTalla; // ['XS' => 10, 'S' => 15, ...]
    private string $descripcion;
    private ?int $colorId = null;
    private ?int $telaId = null;
    private ?int $tipoMangaId = null;
    private ?int $tipoBrocheId = null;
    private bool $tieneBolsillos = false;
    private bool $tieneReflectivo = false;

    private function __construct(
        string $nombrePrenda,
        int $cantidadTotal,
        array $cantidadTalla
    ) {
        $this->nombrePrenda = $nombrePrenda;
        $this->cantidadTotal = $cantidadTotal;
        $this->cantidadTalla = $cantidadTalla;
        $this->cantidadEntregada = 0;
        $this->descripcion = '';
    }

    /**
     * Factory method: Crear prenda
     */
    public static function crear(
        string $nombrePrenda,
        int $cantidadTotal,
        array $cantidadTalla = []
    ): self {
        if (empty($nombrePrenda)) {
            throw new \DomainException('El nombre de la prenda no puede estar vacío');
        }

        if ($cantidadTotal <= 0) {
            throw new \DomainException('La cantidad debe ser mayor a 0');
        }

        return new self($nombrePrenda, $cantidadTotal, $cantidadTalla);
    }

    /**
     * Registrar entrega de prenda
     */
    public function registrarEntrega(float $cantidad): void
    {
        if ($cantidad < 0) {
            throw new \DomainException('La cantidad entregada no puede ser negativa');
        }

        $nuevaTotal = $this->cantidadEntregada + $cantidad;

        if ($nuevaTotal > $this->cantidadTotal) {
            throw new \DomainException(
                "No se puede entregar más de lo solicitado. Entregadas: {$this->cantidadEntregada}, "
                . "Solicitadas: {$this->cantidadTotal}"
            );
        }

        $this->cantidadEntregada = $nuevaTotal;
    }

    /**
     * Agregar talla a la prenda
     */
    public function agregarTalla(string $talla, int $cantidad): void
    {
        if (empty($talla) || $cantidad <= 0) {
            throw new \DomainException('Talla y cantidad válidas requeridas');
        }

        $this->cantidadTalla[$talla] = ($this->cantidadTalla[$talla] ?? 0) + $cantidad;
    }

    /**
     * Verificar si está completamente entregada
     */
    public function estaCompleta(): bool
    {
        return $this->cantidadEntregada >= $this->cantidadTotal;
    }

    /**
     * Obtener porcentaje de entrega
     */
    public function getPorcentajeEntrega(): float
    {
        if ($this->cantidadTotal === 0) {
            return 0;
        }
        return round(($this->cantidadEntregada / $this->cantidadTotal) * 100, 2);
    }

    // ===== GETTERS =====

    public function getNombrePrenda(): string
    {
        return $this->nombrePrenda;
    }

    public function getCantidadTotal(): int
    {
        return $this->cantidadTotal;
    }

    public function getCantidadEntregada(): float
    {
        return $this->cantidadEntregada;
    }

    public function getCantidadPendiente(): float
    {
        return $this->cantidadTotal - $this->cantidadEntregada;
    }

    public function getCantidadTalla(): array
    {
        return $this->cantidadTalla;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): self
    {
        $this->descripcion = $descripcion;
        return $this;
    }

    public function getColorId(): ?int
    {
        return $this->colorId;
    }

    public function setColorId(?int $colorId): self
    {
        $this->colorId = $colorId;
        return $this;
    }

    public function getTelaId(): ?int
    {
        return $this->telaId;
    }

    public function setTelaId(?int $telaId): self
    {
        $this->telaId = $telaId;
        return $this;
    }

    public function getTipoMangaId(): ?int
    {
        return $this->tipoMangaId;
    }

    public function setTipoMangaId(?int $tipoMangaId): self
    {
        $this->tipoMangaId = $tipoMangaId;
        return $this;
    }

    public function getTipoBrocheId(): ?int
    {
        return $this->tipoBrocheId;
    }

    public function setTipoBrocheId(?int $tipoBrocheId): self
    {
        $this->tipoBrocheId = $tipoBrocheId;
        return $this;
    }

    public function tieneBolsillos(): bool
    {
        return $this->tieneBolsillos;
    }

    public function setTieneBolsillos(bool $tieneBolsillos): self
    {
        $this->tieneBolsillos = $tieneBolsillos;
        return $this;
    }

    public function tieneReflectivo(): bool
    {
        return $this->tieneReflectivo;
    }

    public function setTieneReflectivo(bool $tieneReflectivo): self
    {
        $this->tieneReflectivo = $tieneReflectivo;
        return $this;
    }
}
