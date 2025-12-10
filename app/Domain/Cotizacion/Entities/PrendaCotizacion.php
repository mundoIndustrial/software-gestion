<?php

namespace App\Domain\Cotizacion\Entities;

use App\Domain\Cotizacion\ValueObjects\RutaImagen;

/**
 * PrendaCotizacion - Entity que representa una prenda dentro de una cotización
 *
 * Una prenda tiene:
 * - Datos básicos (nombre, descripción, cantidad)
 * - Variantes (género, manga, bolsillos, etc.)
 * - Fotos, telas, tallas
 */
final class PrendaCotizacion
{
    private int $id;
    private string $nombreProducto;
    private string $descripcion;
    private int $cantidad;
    private array $variantes = [];
    private array $fotos = [];
    private array $telas = [];
    private array $tallas = [];

    public function __construct(
        int $id,
        string $nombreProducto,
        string $descripcion,
        int $cantidad
    ) {
        $this->validar($nombreProducto, $descripcion, $cantidad);

        $this->id = $id;
        $this->nombreProducto = $nombreProducto;
        $this->descripcion = $descripcion;
        $this->cantidad = $cantidad;
    }

    /**
     * Factory method para crear una nueva prenda
     */
    public static function crear(
        string $nombreProducto,
        string $descripcion,
        int $cantidad
    ): self {
        return new self(0, $nombreProducto, $descripcion, $cantidad);
    }

    /**
     * Validar datos de la prenda
     */
    private function validar(string $nombreProducto, string $descripcion, int $cantidad): void
    {
        if (empty(trim($nombreProducto))) {
            throw new \InvalidArgumentException('El nombre del producto no puede estar vacío');
        }

        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad debe ser mayor a 0');
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
     * Obtener nombre del producto
     */
    public function nombreProducto(): string
    {
        return $this->nombreProducto;
    }

    /**
     * Obtener descripción
     */
    public function descripcion(): string
    {
        return $this->descripcion;
    }

    /**
     * Obtener cantidad
     */
    public function cantidad(): int
    {
        return $this->cantidad;
    }

    /**
     * Agregar variante
     */
    public function agregarVariante(string $clave, mixed $valor): void
    {
        $this->variantes[$clave] = $valor;
    }

    /**
     * Obtener variantes
     */
    public function variantes(): array
    {
        return $this->variantes;
    }

    /**
     * Obtener variante específica
     */
    public function obtenerVariante(string $clave): mixed
    {
        return $this->variantes[$clave] ?? null;
    }

    /**
     * Agregar foto
     */
    public function agregarFoto(RutaImagen $ruta): void
    {
        $this->fotos[] = $ruta;
    }

    /**
     * Obtener fotos
     */
    public function fotos(): array
    {
        return $this->fotos;
    }

    /**
     * Agregar tela
     */
    public function agregarTela(array $datos): void
    {
        $this->telas[] = $datos;
    }

    /**
     * Obtener telas
     */
    public function telas(): array
    {
        return $this->telas;
    }

    /**
     * Agregar talla
     */
    public function agregarTalla(string $talla): void
    {
        if (!in_array($talla, $this->tallas)) {
            $this->tallas[] = $talla;
        }
    }

    /**
     * Obtener tallas
     */
    public function tallas(): array
    {
        return $this->tallas;
    }

    /**
     * Verificar si tiene fotos
     */
    public function tieneFotos(): bool
    {
        return !empty($this->fotos);
    }

    /**
     * Verificar si tiene telas
     */
    public function tieneTelas(): bool
    {
        return !empty($this->telas);
    }

    /**
     * Verificar si tiene tallas
     */
    public function tieneTallas(): bool
    {
        return !empty($this->tallas);
    }

    /**
     * Obtener cantidad de fotos
     */
    public function cantidadFotos(): int
    {
        return count($this->fotos);
    }

    /**
     * Obtener cantidad de telas
     */
    public function cantidadTelas(): int
    {
        return count($this->telas);
    }

    /**
     * Obtener cantidad de tallas
     */
    public function cantidadTallas(): int
    {
        return count($this->tallas);
    }

    /**
     * Convertir a array para persistencia
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nombre_producto' => $this->nombreProducto,
            'descripcion' => $this->descripcion,
            'cantidad' => $this->cantidad,
            'variantes' => $this->variantes,
            'fotos' => array_map(fn($f) => (string) $f, $this->fotos),
            'telas' => $this->telas,
            'tallas' => $this->tallas,
        ];
    }
}
