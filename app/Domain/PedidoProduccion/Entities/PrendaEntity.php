<?php

namespace App\Domain\PedidoProduccion\Entities;

use DateTime;
use InvalidArgumentException;

/**
 * PrendaEntity
 * 
 * Entity que representa una prenda dentro de un pedido de producción
 * Tiene identidad propia y ciclo de vida
 */
class PrendaEntity
{
    private string $id;
    private string $numero;
    private string $descripcion;
    private int $cantidad;
    private array $tallas = [];
    private DateTime $fechaAgregada;

    public function __construct(
        string $numero,
        string $descripcion,
        int $cantidad = 1,
        array $tallas = []
    ) {
        $this->id = uniqid('prn_', true);
        $this->numero = self::validarNumero($numero);
        $this->descripcion = self::validarDescripcion($descripcion);
        $this->cantidad = self::validarCantidad($cantidad);
        $this->tallas = self::validarTallas($tallas);
        $this->fechaAgregada = new DateTime();
    }

    /**
     * Factory para restaurar desde BD
     */
    public static function restaurarDesdeBD(array $datos): self
    {
        $instance = new self(
            $datos['numero'],
            $datos['descripcion'],
            $datos['cantidad'],
            $datos['tallas'] ?? []
        );

        $instance->id = $datos['id'];
        $instance->fechaAgregada = new DateTime($datos['fecha_agregada']);

        return $instance;
    }

    /**
     * Validadores
     */
    private static function validarNumero(string $numero): string
    {
        $numero = trim($numero);

        if (empty($numero)) {
            throw new InvalidArgumentException("Número de prenda es requerido");
        }

        if (strlen($numero) > 50) {
            throw new InvalidArgumentException("Número de prenda no puede exceder 50 caracteres");
        }

        return $numero;
    }

    private static function validarDescripcion(string $descripcion): string
    {
        $descripcion = trim($descripcion);

        if (strlen($descripcion) > 500) {
            throw new InvalidArgumentException("Descripción no puede exceder 500 caracteres");
        }

        return $descripcion;
    }

    private static function validarCantidad(int $cantidad): int
    {
        if ($cantidad < 1) {
            throw new InvalidArgumentException("Cantidad debe ser mayor a 0");
        }

        if ($cantidad > 10000) {
            throw new InvalidArgumentException("Cantidad excede límite permitido");
        }

        return $cantidad;
    }

    private static function validarTallas(array $tallas): array
    {
        if (empty($tallas)) {
            return [];
        }

        foreach ($tallas as $talla) {
            if (empty($talla['nombre']) || empty($talla['cantidad'])) {
                throw new InvalidArgumentException("Talla debe tener nombre y cantidad");
            }

            if ((int)$talla['cantidad'] < 1) {
                throw new InvalidArgumentException("Cantidad de talla debe ser mayor a 0");
            }
        }

        return $tallas;
    }

    /**
     * Agregar talla
     */
    public function agregarTalla(string $nombre, int $cantidad): void
    {
        if (empty($nombre)) {
            throw new InvalidArgumentException("Nombre de talla no puede estar vacío");
        }

        if ($cantidad < 1) {
            throw new InvalidArgumentException("Cantidad de talla debe ser mayor a 0");
        }

        // Verificar que no existe
        foreach ($this->tallas as $talla) {
            if ($talla['nombre'] === $nombre) {
                throw new InvalidArgumentException("La talla {$nombre} ya existe");
            }
        }

        $this->tallas[] = [
            'nombre' => $nombre,
            'cantidad' => $cantidad,
        ];
    }

    /**
     * Getters
     */
    public function getId(): string
    {
        return $this->id;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    public function getTallas(): array
    {
        return $this->tallas;
    }

    public function getFechaAgregada(): DateTime
    {
        return $this->fechaAgregada;
    }

    public function getCantidadTallas(): int
    {
        return count($this->tallas);
    }

    /**
     * Convertir a array para persistencia
     */
    public function aArray(): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'descripcion' => $this->descripcion,
            'cantidad' => $this->cantidad,
            'tallas' => $this->tallas,
            'fecha_agregada' => $this->fechaAgregada->format('Y-m-d H:i:s'),
        ];
    }
}
