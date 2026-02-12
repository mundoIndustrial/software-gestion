<?php

namespace App\Application\Cotizacion\DTOs;

/**
 * ActualizarCotizacionRequestDTO
 *
 * DTO de entrada para actualizar una cotización existente.
 * Diseñado para desacoplar la capa Application de Illuminate\Http\Request.
 */
final readonly class ActualizarCotizacionRequestDTO
{
    public function __construct(
        public ?int $clienteId,
        public ?string $nombreCliente,
        public bool $esBorrador,
        public ?string $tipoVenta,
        public ?string $tipoCotizacionCodigo,
        public array $especificaciones,
        public array $prendasRecibidas,
    ) {
    }
}
