<?php

namespace App\Domain\Pedidos\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object para Asignación de Color
 * Representa un color con su cantidad en una asignación
 */
class AsignacionColor
{
    private string $color;
    private int $cantidad;
    private string $fecha;

    public function __construct(string $color, int $cantidad, ?string $fecha = null)
    {
        $this->validarColor($color);
        $this->validarCantidad($cantidad);
        
        $this->color = strtoupper(trim($color));
        $this->cantidad = $cantidad;
        $this->fecha = $fecha ?? now()->format('Y-m-d H:i:s');
    }

    /**
     * Validar nombre del color
     */
    private function validarColor(string $color): void
    {
        $colorLimpio = trim($color);
        
        if (empty($colorLimpio)) {
            throw new InvalidArgumentException('El nombre del color no puede estar vacío');
        }
        
        if (strlen($colorLimpio) > 50) {
            throw new InvalidArgumentException('El nombre del color no puede exceder 50 caracteres');
        }
        
        // Validar caracteres permitidos (letras, números, espacios, guiones)
        if (!preg_match('/^[a-zA-Z0-9\s\-]+$/', $colorLimpio)) {
            throw new InvalidArgumentException('El nombre del color solo puede contener letras, números, espacios y guiones');
        }
    }

    /**
     * Validar cantidad
     */
    private function validarCantidad(int $cantidad): void
    {
        if ($cantidad <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a 0');
        }
        
        if ($cantidad > 9999) {
            throw new InvalidArgumentException('La cantidad no puede exceder 9999');
        }
    }

    /**
     * Obtener nombre del color
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * Obtener cantidad
     */
    public function getCantidad(): int
    {
        return $this->cantidad;
    }

    /**
     * Obtener fecha
     */
    public function getFecha(): string
    {
        return $this->fecha;
    }

    /**
     * Actualizar cantidad
     */
    public function actualizarCantidad(int $nuevaCantidad): void
    {
        $this->validarCantidad($nuevaCantidad);
        $this->cantidad = $nuevaCantidad;
        $this->fecha = now()->format('Y-m-d H:i:s');
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'color' => $this->color,
            'cantidad' => $this->cantidad,
            'fecha' => $this->fecha
        ];
    }

    /**
     * Convertir a JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Crear desde array
     */
    public static function fromArray(array $datos): self
    {
        return new self(
            $datos['color'] ?? '',
            $datos['cantidad'] ?? 0,
            $datos['fecha'] ?? null
        );
    }

    /**
     * Crear desde JSON
     */
    public static function fromJson(string $json): self
    {
        $datos = json_decode($json, true);
        
        if (!is_array($datos)) {
            throw new InvalidArgumentException('JSON inválido para AsignacionColor');
        }
        
        return self::fromArray($datos);
    }

    /**
     * Comparar con otra asignación
     */
    public function equals(AsignacionColor $otra): bool
    {
        return $this->color === $otra->getColor() && $this->cantidad === $otra->getCantidad();
    }

    /**
     * Verificar si es el mismo color (ignorando cantidad)
     */
    public function esMismoColor(AsignacionColor $otra): bool
    {
        return $this->color === $otra->getColor();
    }

    /**
     * Sumar cantidades
     */
    public function sumarCantidad(AsignacionColor $otra): AsignacionColor
    {
        if (!$this->esMismoColor($otra)) {
            throw new InvalidArgumentException('No se pueden sumar cantidades de colores diferentes');
        }
        
        return new self(
            $this->color,
            $this->cantidad + $otra->getCantidad(),
            $this->fecha
        );
    }

    /**
     * Validar si el color es válido según un catálogo
     */
    public function esValidoSegunCatalogo(array $coloresPermitidos): bool
    {
        return in_array($this->color, $coloresPermitidos);
    }

    /**
     * Obtener representación como string
     */
    public function __toString(): string
    {
        return "{$this->color} ({$this->cantidad})";
    }

    /**
     * Serialización
     */
    public function serialize(): string
    {
        return $this->toJson();
    }

    /**
     * Deserialización
     */
    public function unserialize(string $data): void
    {
        $asignacion = self::fromJson($data);
        
        $this->color = $asignacion->getColor();
        $this->cantidad = $asignacion->getCantidad();
        $this->fecha = $asignacion->getFecha();
    }
}
