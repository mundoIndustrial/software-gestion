<?php

namespace App\ValueObjects;

/**
 * PrendaData
 * 
 * Value Object para encapsular datos de una prenda
 * Implementa getters/setters para acceso controlado a propiedades
 * 
 * CUMPLE: Encapsulación, Validación de tallas, Transformación
 */
class PrendaData
{
    private int $numero_pedido;
    private string $nombre_prenda;
    private array $cantidad_talla = [];
    private int $cantidad_total = 0;

    /**
     * Constructor privado - usar factory methods
     */
    private function __construct(
        int $numero_pedido,
        string $nombre_prenda,
        array $cantidad_talla = []
    ) {
        $this->numero_pedido = $numero_pedido;
        $this->nombre_prenda = $nombre_prenda;
        $this->cantidad_talla = $cantidad_talla;
        $this->calcularCantidadTotal();
    }

    /**
     * Factory method: crear desde array
     */
    public static function fromArray(array $data): self
    {
        $cantidadTalla = $data['cantidad_talla'] ?? [];
        
        // Si viene como JSON string, decodificar
        if (is_string($cantidadTalla)) {
            $cantidadTalla = json_decode($cantidadTalla, true) ?? [];
        }

        return new self(
            numero_pedido: (int) ($data['numero_pedido'] ?? 0),
            nombre_prenda: $data['nombre_prenda'] ?? '',
            cantidad_talla: $cantidadTalla
        );
    }

    /**
     * Factory method: crear desde modelo
     */
    public static function fromModel($modelo): self
    {
        return self::fromArray($modelo->toArray());
    }

    // GETTERS

    public function getNumeroPedido(): int
    {
        return $this->numero_pedido;
    }

    public function getNombrePrenda(): string
    {
        return $this->nombre_prenda;
    }

    public function getCantidadTalla(): array
    {
        return $this->cantidad_talla;
    }

    public function getCantidadTallaPorTalla(string $talla): int
    {
        return $this->cantidad_talla[$talla] ?? 0;
    }

    public function getCantidadTotal(): int
    {
        return $this->cantidad_total;
    }

    public function getTallas(): array
    {
        return array_keys($this->cantidad_talla);
    }

    // SETTERS

    public function setCantidadTalla(array $cantidad_talla): self
    {
        $this->cantidad_talla = $cantidad_talla;
        $this->calcularCantidadTotal();
        return $this;
    }

    public function addTalla(string $talla, int $cantidad): self
    {
        $this->cantidad_talla[$talla] = ($this->cantidad_talla[$talla] ?? 0) + $cantidad;
        $this->calcularCantidadTotal();
        return $this;
    }

    public function setTallaCantidad(string $talla, int $cantidad): self
    {
        if ($cantidad > 0) {
            $this->cantidad_talla[$talla] = $cantidad;
        } else {
            unset($this->cantidad_talla[$talla]);
        }
        $this->calcularCantidadTotal();
        return $this;
    }

    public function removeTalla(string $talla): self
    {
        unset($this->cantidad_talla[$talla]);
        $this->calcularCantidadTotal();
        return $this;
    }

    /**
     * Convertir a array para BD (JSON)
     */
    public function toArray(): array
    {
        return [
            'numero_pedido' => $this->numero_pedido,
            'nombre_prenda' => $this->nombre_prenda,
            'cantidad_talla' => json_encode($this->cantidad_talla),
            'cantidad_total' => $this->cantidad_total
        ];
    }

    /**
     * Convertir a array para respuesta API
     */
    public function toApiArray(): array
    {
        return [
            'nombre_prenda' => $this->nombre_prenda,
            'cantidad_talla' => $this->cantidad_talla,
            'cantidad_total' => $this->cantidad_total,
            'tallas' => $this->getTallas()
        ];
    }

    /**
     * Validar que los datos sean válidos
     */
    public function validate(): bool
    {
        return !empty($this->nombre_prenda) 
            && !empty($this->cantidad_talla)
            && $this->cantidad_total > 0;
    }

    /**
     * Calcular cantidad total sumando todas las tallas
     */
    private function calcularCantidadTotal(): void
    {
        $this->cantidad_total = array_sum($this->cantidad_talla);
    }
}
