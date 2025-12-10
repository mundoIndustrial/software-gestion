<?php

namespace App\Application\Cotizacion\DTOs;

/**
 * ListarCotizacionesDTO - DTO para listar cotizaciones
 *
 * Datos de entrada para el caso de uso de listado
 */
final readonly class ListarCotizacionesDTO
{
    public function __construct(
        public int $usuarioId,
        public string $tipoCotizacionCodigo = '',
        public bool $soloActivas = false,
        public int $pagina = 1,
        public int $porPagina = 15,
    ) {
    }

    /**
     * Factory method desde array
     */
    public static function desdeArray(array $datos): self
    {
        return new self(
            usuarioId: (int) $datos['usuario_id'] ?? 0,
            tipoCotizacionCodigo: $datos['tipo_cotizacion_codigo'] ?? '',
            soloActivas: (bool) $datos['solo_activas'] ?? false,
            pagina: (int) $datos['pagina'] ?? 1,
            porPagina: (int) $datos['por_pagina'] ?? 15,
        );
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'usuario_id' => $this->usuarioId,
            'tipo_cotizacion_codigo' => $this->tipoCotizacionCodigo,
            'solo_activas' => $this->soloActivas,
            'pagina' => $this->pagina,
            'por_pagina' => $this->porPagina,
        ];
    }
}
