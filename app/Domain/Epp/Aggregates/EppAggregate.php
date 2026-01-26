<?php

namespace App\Domain\Epp\Aggregates;

/**
 * Agregado: EppAggregate
 * 
 * Raíz de agregado para Equipos de Protección Personal (EPP)
 * NOTA: Los campos codigo y categoria NO existen en la tabla epps
 * La tabla solo contiene: id, nombre_completo, marca, tipo, talla, color, descripcion, activo, created_at, updated_at
 */
class EppAggregate
{
    private int $id;
    private string $nombre;
    private ?string $marca;
    private ?string $tipo;
    private ?string $talla;
    private ?string $color;
    private ?string $descripcion;
    private bool $activo;
    private \DateTime $creadoEn;
    private ?\DateTime $actualizadoEn;

    private function __construct(
        int $id,
        string $nombre,
        ?string $marca = null,
        ?string $tipo = null,
        ?string $talla = null,
        ?string $color = null,
        ?string $descripcion = null,
        bool $activo = true
    ) {
        $this->id = $id;
        $this->nombre = trim($nombre);
        $this->marca = $marca;
        $this->tipo = $tipo;
        $this->talla = $talla;
        $this->color = $color;
        $this->descripcion = $descripcion;
        $this->activo = $activo;
        $this->creadoEn = new \DateTime();
    }

    /**
     * Factory method: Crear nuevo EPP
     */
    public static function crear(
        int $id,
        string $nombre,
        ?string $marca = null,
        ?string $descripcion = null
    ): self {
        if (empty($nombre) || strlen($nombre) > 255) {
            throw new \InvalidArgumentException('Nombre del EPP inválido');
        }

        return new self(
            $id,
            $nombre,
            $marca,
            null,
            null,
            null,
            $descripcion
        );
    }

    /**
     * Factory method: Reconstruir desde BD
     */
    public static function reconstruir(
        int $id,
        string $nombre,
        ?string $marca,
        ?string $tipo,
        ?string $talla,
        ?string $color,
        ?string $descripcion,
        bool $activo,
        \DateTime $creadoEn,
        ?\DateTime $actualizadoEn
    ): self {
        $agregado = new self(
            $id,
            $nombre,
            $marca,
            $tipo,
            $talla,
            $color,
            $descripcion,
            $activo
        );

        $agregado->creadoEn = $creadoEn;
        $agregado->actualizadoEn = $actualizadoEn;

        return $agregado;
    }

    // ==================== Getters ====================

    public function id(): int
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function marca(): ?string
    {
        return $this->marca;
    }

    public function tipo(): ?string
    {
        return $this->tipo;
    }

    public function talla(): ?string
    {
        return $this->talla;
    }

    public function color(): ?string
    {
        return $this->color;
    }

    public function descripcion(): ?string
    {
        return $this->descripcion;
    }

    public function estaActivo(): bool
    {
        return $this->activo;
    }

    public function creadoEn(): \DateTime
    {
        return $this->creadoEn;
    }

    public function actualizadoEn(): ?\DateTime
    {
        return $this->actualizadoEn;
    }

    /**
     * Activar EPP
     */
    public function activar(): void
    {
        $this->activo = true;
        $this->actualizadoEn = new \DateTime();
    }

    /**
     * Desactivar EPP
     */
    public function desactivar(): void
    {
        $this->activo = false;
        $this->actualizadoEn = new \DateTime();
    }
}
