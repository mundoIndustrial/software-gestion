<?php

namespace App\Domain\Cotizacion\Entities;

use App\Domain\Cotizacion\ValueObjects\RutaImagen;

/**
 * LogoCotizacion - Entity que representa el logo/bordado de una cotización
 *
 * Un logo tiene:
 * - Descripción
 * - Imágenes
 * - Técnicas de bordado
 * - Observaciones técnicas
 * - Ubicaciones
 * - Observaciones generales
 */
final class LogoCotizacion
{
    private int $id;
    private string $descripcion;
    private array $imagenes = [];
    private array $tecnicas = [];
    private string $observacionesTecnicas;
    private array $ubicaciones = [];
    private string $observacionesGenerales;

    public function __construct(
        int $id,
        string $descripcion,
        string $observacionesTecnicas = '',
        string $observacionesGenerales = ''
    ) {
        $this->validar($descripcion);

        $this->id = $id;
        $this->descripcion = $descripcion;
        $this->observacionesTecnicas = $observacionesTecnicas;
        $this->observacionesGenerales = $observacionesGenerales;
    }

    /**
     * Factory method para crear un nuevo logo
     */
    public static function crear(
        string $descripcion,
        string $observacionesTecnicas = '',
        string $observacionesGenerales = ''
    ): self {
        return new self(0, $descripcion, $observacionesTecnicas, $observacionesGenerales);
    }

    /**
     * Validar datos del logo
     */
    private function validar(string $descripcion): void
    {
        if (empty(trim($descripcion))) {
            throw new \InvalidArgumentException('La descripción del logo no puede estar vacía');
        }
    }

    /**
     * Obtener ID
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * Obtener descripción
     */
    public function descripcion(): string
    {
        return $this->descripcion;
    }

    /**
     * Actualizar descripción
     */
    public function actualizarDescripcion(string $descripcion): void
    {
        $this->validar($descripcion);
        $this->descripcion = $descripcion;
    }

    /**
     * Agregar imagen
     */
    public function agregarImagen(RutaImagen $ruta): void
    {
        $this->imagenes[] = $ruta;
    }

    /**
     * Obtener imágenes
     */
    public function imagenes(): array
    {
        return $this->imagenes;
    }

    /**
     * Agregar técnica
     */
    public function agregarTecnica(string $tecnica): void
    {
        if (!in_array($tecnica, $this->tecnicas)) {
            $this->tecnicas[] = $tecnica;
        }
    }

    /**
     * Obtener técnicas
     */
    public function tecnicas(): array
    {
        return $this->tecnicas;
    }

    /**
     * Obtener observaciones técnicas
     */
    public function observacionesTecnicas(): string
    {
        return $this->observacionesTecnicas;
    }

    /**
     * Actualizar observaciones técnicas
     */
    public function actualizarObservacionesTecnicas(string $observaciones): void
    {
        $this->observacionesTecnicas = $observaciones;
    }

    /**
     * Agregar ubicación
     */
    public function agregarUbicacion(string $ubicacion): void
    {
        if (!in_array($ubicacion, $this->ubicaciones)) {
            $this->ubicaciones[] = $ubicacion;
        }
    }

    /**
     * Obtener ubicaciones
     */
    public function ubicaciones(): array
    {
        return $this->ubicaciones;
    }

    /**
     * Obtener observaciones generales
     */
    public function observacionesGenerales(): string
    {
        return $this->observacionesGenerales;
    }

    /**
     * Actualizar observaciones generales
     */
    public function actualizarObservacionesGenerales(string $observaciones): void
    {
        $this->observacionesGenerales = $observaciones;
    }

    /**
     * Verificar si tiene imágenes
     */
    public function tieneImagenes(): bool
    {
        return !empty($this->imagenes);
    }

    /**
     * Verificar si tiene técnicas
     */
    public function tieneTecnicas(): bool
    {
        return !empty($this->tecnicas);
    }

    /**
     * Verificar si tiene ubicaciones
     */
    public function tieneUbicaciones(): bool
    {
        return !empty($this->ubicaciones);
    }

    /**
     * Obtener cantidad de imágenes
     */
    public function cantidadImagenes(): int
    {
        return count($this->imagenes);
    }

    /**
     * Convertir a array para persistencia
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'descripcion' => $this->descripcion,
            'imagenes' => array_map(fn($i) => (string) $i, $this->imagenes),
            'tecnicas' => $this->tecnicas,
            'observaciones_tecnicas' => $this->observacionesTecnicas,
            'ubicaciones' => $this->ubicaciones,
            'observaciones_generales' => $this->observacionesGenerales,
        ];
    }
}
