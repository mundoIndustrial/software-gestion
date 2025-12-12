<?php

namespace App\Domain\Cotizacion\Entities;

use App\Domain\Cotizacion\ValueObjects\RutaImagen;

/**
 * ReflectivoCotizacion - Entity que representa el reflectivo de una cotización
 *
 * Un reflectivo tiene:
 * - Descripción
 * - Imágenes
 * - Ubicación
 * - Observaciones generales
 * 
 * NOTA: A diferencia de LogoCotizacion, NO tiene técnicas
 */
final class ReflectivoCotizacion
{
    private int $id;
    private string $descripcion;
    private array $imagenes = [];
    private string $ubicacion;
    private array $observacionesGenerales = [];

    public function __construct(
        int $id,
        string $descripcion,
        string $ubicacion = ''
    ) {
        $this->validar($descripcion);

        $this->id = $id;
        $this->descripcion = $descripcion;
        $this->ubicacion = $ubicacion;
    }

    /**
     * Factory method para crear un nuevo reflectivo
     */
    public static function crear(
        string $descripcion,
        string $ubicacion = ''
    ): self {
        return new self(0, $descripcion, $ubicacion);
    }

    /**
     * Validar datos del reflectivo
     */
    private function validar(string $descripcion): void
    {
        if (empty(trim($descripcion))) {
            throw new \InvalidArgumentException('La descripción del reflectivo no puede estar vacía');
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
     * Obtener ubicación
     */
    public function ubicacion(): string
    {
        return $this->ubicacion;
    }

    /**
     * Actualizar ubicación
     */
    public function actualizarUbicacion(string $ubicacion): void
    {
        $this->ubicacion = $ubicacion;
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
     * Agregar observación general
     */
    public function agregarObservacion(array $observacion): void
    {
        $this->observacionesGenerales[] = $observacion;
    }

    /**
     * Obtener observaciones generales
     */
    public function observacionesGenerales(): array
    {
        return $this->observacionesGenerales;
    }

    /**
     * Verificar si tiene imágenes
     */
    public function tieneImagenes(): bool
    {
        return !empty($this->imagenes);
    }

    /**
     * Verificar si tiene observaciones
     */
    public function tieneObservaciones(): bool
    {
        return !empty($this->observacionesGenerales);
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
            'ubicacion' => $this->ubicacion,
            'observaciones_generales' => $this->observacionesGenerales,
        ];
    }
}
