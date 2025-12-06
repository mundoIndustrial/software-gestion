<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

/**
 * DTO para búsqueda y presentación de cotizaciones
 * Principio de responsabilidad única: encapsula solo datos de cotización
 */
class CotizacionSearchDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $numero,
        public readonly string $cliente,
        public readonly string $asesora,
        public readonly string $formaPago,
        public readonly int $prendasCount,
    ) {}

    /**
     * Factory method para crear desde modelo Cotizacion
     */
    public static function fromModel($cotizacion): self
    {
        return new self(
            id: $cotizacion->id,
            numero: $cotizacion->numero_cotizacion ?? '#' . $cotizacion->id,
            cliente: $cotizacion->cliente ?? '',
            asesora: $cotizacion->asesora ?? '',
            formaPago: self::extractFormaPago($cotizacion),
            prendasCount: $cotizacion->prendasCotizaciones->count()
        );
    }

    /**
     * Extrae forma de pago de estructura anidada
     */
    private static function extractFormaPago($cotizacion): string
    {
        if (is_array($cotizacion->especificaciones)) {
            $formaPagoArray = $cotizacion->especificaciones['forma_pago'] ?? null;
            if (is_array($formaPagoArray) && count($formaPagoArray) > 0) {
                return $formaPagoArray[0];
            } elseif (is_string($formaPagoArray)) {
                return $formaPagoArray;
            }
        } elseif (is_object($cotizacion->especificaciones)) {
            $formaPagoArray = $cotizacion->especificaciones->forma_pago ?? null;
            if (is_array($formaPagoArray) && count($formaPagoArray) > 0) {
                return $formaPagoArray[0];
            } elseif (is_string($formaPagoArray)) {
                return $formaPagoArray;
            }
        }

        return $cotizacion->forma_pago ?? '';
    }

    /**
     * Convierte a array para JavaScript
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'cliente' => $this->cliente,
            'asesora' => $this->asesora,
            'formaPago' => $this->formaPago,
            'prendasCount' => $this->prendasCount,
        ];
    }

    /**
     * Filtra colección por nombre de asesor
     */
    public static function filterByAsesor(Collection $dtos, string $asesorNombre): Collection
    {
        return $dtos->filter(fn($dto) => $dto->asesora === $asesorNombre);
    }
}
