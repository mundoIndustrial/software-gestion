<?php

namespace App\Application\Pedidos\DTOs;

/**
 * ObtenerEstadisticasDashboardDTO
 * 
 * DTO para obtener estadísticas generales del dashboard del asesor
 */
class ObtenerEstadisticasDashboardDTO
{
    public function __construct(
        public readonly ?int $asesorId = null
    ) {}

    /**
     * Crear desde una solicitud HTTP
     */
    public static function fromRequest(): self
    {
        return new self(
            asesorId: \Illuminate\Support\Facades\Auth::id()
        );
    }

    /**
     * Crear instancia estática
     */
    public static function crear(): self
    {
        return self::fromRequest();
    }
}
