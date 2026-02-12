<?php

namespace App\Application\Cotizacion\DTOs;

/**
 * ActualizarImagenesCotizacionDTO
 *
 * DTO para actualización/sincronización de imágenes en update.
 * Diseñado para desacoplar la capa Application de Illuminate\Http\Request.
 */
final readonly class ActualizarImagenesCotizacionDTO
{
    public function __construct(
        public array $fotosAEliminar,
        public array $prendasRecibidas,
        public array $hayFotosPrendaNuevasPorIndex,
        public array $hayFotosTelaNuevasPorIndex,
        public array $logoFotosGuardadas,
        public int $logoArchivosNuevosCount,
    ) {
    }
}
