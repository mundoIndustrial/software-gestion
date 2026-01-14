<?php

namespace App\Domain\Procesos\Entities;

use App\Domain\Shared\Entity;

/**
 * Entity: TipoProceso
 * 
 * Representa un tipo de proceso disponible en el sistema
 * (Reflectivo, Bordado, Estampado, DTF, Sublimado)
 */
class TipoProceso extends Entity
{
    protected $nombre;
    protected $slug;
    protected $descripcion;
    protected $color;
    protected $icono;
    protected $activo;

    public function __construct(
        ?int $id,
        string $nombre,
        string $slug,
        ?string $descripcion = null,
        ?string $color = null,
        ?string $icono = null,
        bool $activo = true
    ) {
        parent::__construct($id);
        $this->nombre = $nombre;
        $this->slug = $slug;
        $this->descripcion = $descripcion;
        $this->color = $color;
        $this->icono = $icono;
        $this->activo = $activo;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getIcono(): ?string
    {
        return $this->icono;
    }

    public function isActivo(): bool
    {
        return $this->activo;
    }

    public function desactivar(): void
    {
        $this->activo = false;
    }

    public function activar(): void
    {
        $this->activo = true;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'slug' => $this->slug,
            'descripcion' => $this->descripcion,
            'color' => $this->color,
            'icono' => $this->icono,
            'activo' => $this->activo,
        ];
    }
}
