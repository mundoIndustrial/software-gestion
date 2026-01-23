<?php

namespace App\Application\Pedidos\DTOs;

/**
 * ObtenerDatosGraficasDashboardDTO
 * 
 * DTO para obtener datos de grÃ¡ficas del dashboard
 */
class ObtenerDatosGraficasDashboardDTO
{
    public function __construct(
        public readonly int $dias = 30,
        public readonly ?int $asesorId = null
    ) {}

    /**
     * Crear desde una solicitud HTTP
     */
    public static function fromRequest(int $dias = 30): self
    {
        return new self(
            dias: $dias,
            asesorId: \Illuminate\Support\Facades\Auth::id()
        );
    }
}

