<?php

namespace App\Domain\Cotizacion\ValueObjects;

/**
 * EstadoCotizacion - Value Object que representa los estados posibles de una cotización
 * 
 * Estados válidos:
 * - BORRADOR: Cotización en edición, no enviada
 * - ENVIADA_CONTADOR: Enviada al contador para revisión
 * - APROBADA_CONTADOR: Aprobada por el contador
 * - ENVIADA_APROBADOR: Enviada al aprobador final
 * - APROBADA_APROBADOR: Aprobada por el aprobador final
 * - ACEPTADA: Cliente aceptó la cotización, se crea pedido
 * - RECHAZADA: Cliente rechazó la cotización
 */
enum EstadoCotizacion: string
{
    case BORRADOR = 'BORRADOR';
    case ENVIADA_CONTADOR = 'ENVIADA_CONTADOR';
    case APROBADA_CONTADOR = 'APROBADA_CONTADOR';
    case ENVIADA_APROBADOR = 'ENVIADA_APROBADOR';
    case APROBADA_APROBADOR = 'APROBADA_APROBADOR';
    case ACEPTADA = 'ACEPTADA';
    case RECHAZADA = 'RECHAZADA';

    /**
     * Obtener etiqueta legible del estado
     */
    public function label(): string
    {
        return match ($this) {
            self::BORRADOR => 'Borrador',
            self::ENVIADA_CONTADOR => 'Enviada a Contador',
            self::APROBADA_CONTADOR => 'Aprobada por Contador',
            self::ENVIADA_APROBADOR => 'Enviada a Aprobador',
            self::APROBADA_APROBADOR => 'Aprobada por Aprobador',
            self::ACEPTADA => 'Aceptada',
            self::RECHAZADA => 'Rechazada',
        };
    }

    /**
     * Verificar si es un estado final (no puede cambiar)
     */
    public function esEstadoFinal(): bool
    {
        return in_array($this, [
            self::ACEPTADA,
            self::RECHAZADA,
        ]);
    }

    /**
     * Verificar si es un estado de borrador
     */
    public function esBorrador(): bool
    {
        return $this === self::BORRADOR;
    }

    /**
     * Obtener los siguientes estados posibles desde el estado actual
     */
    public function siguientesEstadosPosibles(): array
    {
        return match ($this) {
            self::BORRADOR => [
                self::ENVIADA_CONTADOR,
                self::RECHAZADA,
            ],
            self::ENVIADA_CONTADOR => [
                self::APROBADA_CONTADOR,
                self::RECHAZADA,
                self::BORRADOR, // Puede volver a borrador para editar
            ],
            self::APROBADA_CONTADOR => [
                self::ENVIADA_APROBADOR,
                self::RECHAZADA,
            ],
            self::ENVIADA_APROBADOR => [
                self::APROBADA_APROBADOR,
                self::RECHAZADA,
            ],
            self::APROBADA_APROBADOR => [
                self::ACEPTADA,
                self::RECHAZADA,
            ],
            self::ACEPTADA => [], // Estado final
            self::RECHAZADA => [], // Estado final
        };
    }

    /**
     * Verificar si puede transicionar a un nuevo estado
     */
    public function puedeTransicionarA(self $nuevoEstado): bool
    {
        return in_array($nuevoEstado, $this->siguientesEstadosPosibles());
    }

    /**
     * Verificar si requiere aprobación
     */
    public function requiereAprobacion(): bool
    {
        return in_array($this, [
            self::ENVIADA_CONTADOR,
            self::ENVIADA_APROBADOR,
        ]);
    }

    /**
     * Obtener el color para UI (Bootstrap)
     */
    public function colorUI(): string
    {
        return match ($this) {
            self::BORRADOR => 'secondary',
            self::ENVIADA_CONTADOR => 'info',
            self::APROBADA_CONTADOR => 'primary',
            self::ENVIADA_APROBADOR => 'warning',
            self::APROBADA_APROBADOR => 'success',
            self::ACEPTADA => 'success',
            self::RECHAZADA => 'danger',
        };
    }
}
