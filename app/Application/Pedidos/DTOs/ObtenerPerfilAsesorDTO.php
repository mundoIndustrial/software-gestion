<?php

namespace App\Application\Pedidos\DTOs;

/**
 * ObtenerPerfilAsesorDTO
 * 
 * DTO para obtener el perfil del asesor
 */
class ObtenerPerfilAsesorDTO
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
