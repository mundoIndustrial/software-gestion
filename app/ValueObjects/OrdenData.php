<?php

namespace App\ValueObjects;

/**
 * OrdenData
 * 
 * Value Object para encapsular datos de una orden
 * Implementa getters/setters para acceso controlado a propiedades
 * 
 * CUMPLE: Encapsulación, Inmutabilidad parcial, Validación
 */
class OrdenData
{
    private int $numero_pedido;
    private string $cliente;
    private string $estado;
    private ?string $fecha_creacion = null;
    private ?string $forma_pago = null;
    private ?string $area = null;
    private int $total_cantidad = 0;
    private int $total_entregado = 0;

    /**
     * Constructor privado - usar factory methods en su lugar
     */
    private function __construct(
        int $numero_pedido,
        string $cliente,
        string $estado,
        ?string $fecha_creacion = null,
        ?string $forma_pago = null,
        ?string $area = null
    ) {
        $this->numero_pedido = $numero_pedido;
        $this->cliente = $cliente;
        $this->estado = $estado;
        $this->fecha_creacion = $fecha_creacion;
        $this->forma_pago = $forma_pago;
        $this->area = $area;
    }

    /**
     * Factory method: crear desde array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            numero_pedido: (int) ($data['numero_pedido'] ?? 0),
            cliente: $data['cliente'] ?? '',
            estado: $data['estado'] ?? 'No iniciado',
            fecha_creacion: $data['fecha_creacion'] ?? null,
            forma_pago: $data['forma_pago'] ?? null,
            area: $data['area'] ?? null
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

    public function getCliente(): string
    {
        return $this->cliente;
    }

    public function getEstado(): string
    {
        return $this->estado;
    }

    public function getFechaCreacion(): ?string
    {
        return $this->fecha_creacion;
    }

    public function getFormaPago(): ?string
    {
        return $this->forma_pago;
    }

    public function getArea(): ?string
    {
        return $this->area;
    }

    public function getTotalCantidad(): int
    {
        return $this->total_cantidad;
    }

    public function getTotalEntregado(): int
    {
        return $this->total_entregado;
    }

    public function getPendiente(): int
    {
        return $this->total_cantidad - $this->total_entregado;
    }

    // SETTERS

    public function setEstado(string $estado): self
    {
        $this->estado = $estado;
        return $this;
    }

    public function setArea(?string $area): self
    {
        $this->area = $area;
        return $this;
    }

    public function setFormaPago(?string $forma_pago): self
    {
        $this->forma_pago = $forma_pago;
        return $this;
    }

    public function setTotalCantidad(int $total): self
    {
        $this->total_cantidad = $total;
        return $this;
    }

    public function setTotalEntregado(int $total): self
    {
        $this->total_entregado = $total;
        return $this;
    }

    /**
     * Convertir a array para respuesta JSON
     */
    public function toArray(): array
    {
        return [
            'numero_pedido' => $this->numero_pedido,
            'cliente' => $this->cliente,
            'estado' => $this->estado,
            'fecha_creacion' => $this->fecha_creacion,
            'forma_pago' => $this->forma_pago,
            'area' => $this->area,
            'total_cantidad' => $this->total_cantidad,
            'total_entregado' => $this->total_entregado,
            'pendiente' => $this->getPendiente()
        ];
    }

    /**
     * Validar que los datos sean válidos
     */
    public function validate(): bool
    {
        return !empty($this->numero_pedido) && !empty($this->cliente);
    }
}
