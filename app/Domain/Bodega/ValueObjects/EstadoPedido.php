<?php

namespace App\Domain\Bodega\ValueObjects;

/**
 * Value Object para representar estados de pedidos
 * Encapsula la lógica de validación y transiciones de estados
 */
class EstadoPedido
{
    private const ESTADOS_VALIDOS = [
        'ENTREGADO' => 'ENTREGADO',
        'EN EJECUCIÓN' => 'EN EJECUCIÓN',
        'NO INICIADO' => 'NO INICIADO',
        'ANULADA' => 'ANULADA',
        'PENDIENTE_SUPERVISOR' => 'PENDIENTE_SUPERVISOR',
        'PENDIENTE_INSUMOS' => 'PENDIENTE_INSUMOS',
        'DEVUELTO_A_ASESORA' => 'DEVUELTO_A_ASESORA'
    ];

    private string $valor;

    public function __construct(string $estado)
    {
        $this->validarEstado($estado);
        $this->valor = $estado;
    }

    private function validarEstado(string $estado): void
    {
        if (!in_array(strtoupper(trim($estado)), array_keys(self::ESTADOS_VALIDOS))) {
            throw new \InvalidArgumentException("Estado de pedido no válido: {$estado}");
        }
    }

    public function getValor(): string
    {
        return $this->valor;
    }

    public function esEntregado(): bool
    {
        return $this->valor === self::ESTADOS_VALIDOS['ENTREGADO'];
    }

    public function estaEnEjecucion(): bool
    {
        return $this->valor === self::ESTADOS_VALIDOS['EN EJECUCIÓN'];
    }

    public function estaNoIniciado(): bool
    {
        return $this->valor === self::ESTADOS_VALIDOS['NO INICIADO'];
    }

    public function estaAnulado(): bool
    {
        return $this->valor === self::ESTADOS_VALIDOS['ANULADA'];
    }

    public function estaPendienteSupervisor(): bool
    {
        return $this->valor === self::ESTADOS_VALIDOS['PENDIENTE_SUPERVISOR'];
    }

    public function estaPendienteInsumos(): bool
    {
        return $this->valor === self::ESTADOS_VALIDOS['PENDIENTE_INSUMOS'];
    }

    public function estaDevueltoAsesora(): bool
    {
        return $this->valor === self::ESTADOS_VALIDOS['DEVUELTO_A_ASESORA'];
    }

    public function estaActivo(): bool
    {
        return !$this->esEntregado() && !$this->estaAnulado();
    }

    public function puedeSerModificado(): bool
    {
        return $this->estaActivo() && !$this->estaPendienteSupervisor();
    }

    public function equals(EstadoPedido $otro): bool
    {
        return $this->valor === $otro->valor;
    }

    public function __toString(): string
    {
        return $this->valor;
    }

    /**
     * Factory methods
     */
    public static function entregado(): self
    {
        return new self(self::ESTADOS_VALIDOS['ENTREGADO']);
    }

    public static function enEjecucion(): self
    {
        return new self(self::ESTADOS_VALIDOS['EN EJECUCIÓN']);
    }

    public static function noIniciado(): self
    {
        return new self(self::ESTADOS_VALIDOS['NO INICIADO']);
    }

    public static function anulado(): self
    {
        return new self(self::ESTADOS_VALIDOS['ANULADA']);
    }

    public static function pendienteSupervisor(): self
    {
        return new self(self::ESTADOS_VALIDOS['PENDIENTE_SUPERVISOR']);
    }

    public static function pendienteInsumos(): self
    {
        return new self(self::ESTADOS_VALIDOS['PENDIENTE_INSUMOS']);
    }

    public static function devueltoAsesora(): self
    {
        return new self(self::ESTADOS_VALIDOS['DEVUELTO_A_ASESORA']);
    }

    /**
     * Obtener todos los estados válidos
     */
    public static function getEstadosValidos(): array
    {
        return self::ESTADOS_VALIDOS;
    }

    /**
     * Crear desde string (normalizado)
     */
    public static function desdeString(string $estado): self
    {
        $estadoNormalizado = strtoupper(trim($estado));
        
        if (!isset(self::ESTADOS_VALIDOS[$estadoNormalizado])) {
            throw new \InvalidArgumentException("Estado no válido: {$estado}");
        }
        
        return new self(self::ESTADOS_VALIDOS[$estadoNormalizado]);
    }
}
