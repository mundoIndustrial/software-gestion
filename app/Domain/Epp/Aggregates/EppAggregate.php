<?php

namespace App\Domain\Epp\Aggregates;

use App\Domain\Epp\ValueObjects\CodigoEpp;
use App\Domain\Epp\ValueObjects\CategoriaEpp;

/**
 * Agregado: EppAggregate
 * 
 * Raíz de agregado para Equipos de Protección Personal (EPP)
 * Encapsula:
 * - Datos del EPP (código, nombre, categoría)
 * - Imágenes asociadas
 * - Invariantes del negocio
 */
class EppAggregate
{
    private int $id;
    private CodigoEpp $codigo;
    private string $nombre;
    private CategoriaEpp $categoria;
    private ?string $descripcion;
    private bool $activo;
    private \DateTime $creadoEn;
    private ?\DateTime $actualizadoEn;
    private ?\DateTime $eliminadoEn;

    /**
     * @var array<EppImagenValue>
     */
    private array $imagenes = [];

    private function __construct(
        int $id,
        CodigoEpp $codigo,
        string $nombre,
        CategoriaEpp $categoria,
        ?string $descripcion = null,
        bool $activo = true
    ) {
        $this->id = $id;
        $this->codigo = $codigo;
        $this->nombre = trim($nombre);
        $this->categoria = $categoria;
        $this->descripcion = $descripcion;
        $this->activo = $activo;
        $this->creadoEn = new \DateTime();
    }

    /**
     * Factory method: Crear nuevo EPP
     */
    public static function crear(
        int $id,
        string $codigo,
        string $nombre,
        string $categoria,
        ?string $descripcion = null
    ): self {
        if (empty($nombre) || strlen($nombre) > 255) {
            throw new \InvalidArgumentException('Nombre del EPP inválido');
        }

        return new self(
            $id,
            new CodigoEpp($codigo),
            $nombre,
            new CategoriaEpp($categoria),
            $descripcion
        );
    }

    /**
     * Factory method: Reconstruir desde BD
     */
    public static function reconstruir(
        int $id,
        string $codigo,
        string $nombre,
        string $categoria,
        ?string $descripcion,
        bool $activo,
        \DateTime $creadoEn,
        ?\DateTime $actualizadoEn,
        ?\DateTime $eliminadoEn
    ): self {
        $agregado = new self(
            $id,
            new CodigoEpp($codigo),
            $nombre,
            new CategoriaEpp($categoria),
            $descripcion,
            $activo
        );

        $agregado->creadoEn = $creadoEn;
        $agregado->actualizadoEn = $actualizadoEn;
        $agregado->eliminadoEn = $eliminadoEn;

        return $agregado;
    }

    // ==================== Getters ====================

    public function id(): int
    {
        return $this->id;
    }

    public function codigo(): CodigoEpp
    {
        return $this->codigo;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }

    public function categoria(): CategoriaEpp
    {
        return $this->categoria;
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

    public function eliminadoEn(): ?\DateTime
    {
        return $this->eliminadoEn;
    }

    /**
     * Agregar imagen al EPP
     */
    public function agregarImagen(EppImagenValue $imagen): void
    {
        // Validar que no haya dos imágenes principales
        if ($imagen->esPrincipal()) {
            foreach ($this->imagenes as $img) {
                if ($img->esPrincipal()) {
                    throw new \InvalidArgumentException('Ya existe una imagen principal');
                }
            }
        }

        $this->imagenes[] = $imagen;
    }

    /**
     * Obtener imágenes del EPP
     *
     * @return array<EppImagenValue>
     */
    public function imagenes(): array
    {
        return $this->imagenes;
    }

    /**
     * Obtener imagen principal
     */
    public function imagenPrincipal(): ?EppImagenValue
    {
        foreach ($this->imagenes as $imagen) {
            if ($imagen->esPrincipal()) {
                return $imagen;
            }
        }

        return null;
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

    /**
     * Marcar como eliminado (soft delete)
     */
    public function eliminar(): void
    {
        $this->eliminadoEn = new \DateTime();
    }
}
