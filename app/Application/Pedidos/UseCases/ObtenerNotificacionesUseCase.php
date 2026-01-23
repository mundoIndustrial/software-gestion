<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerNotificacionesDTO;
use App\Application\Services\Asesores\NotificacionesService;

/**
 * ObtenerNotificacionesUseCase
 * 
 * Use Case para obtener notificaciones del asesor
 * Encapsula la lógica de obtención de notificaciones
 */
class ObtenerNotificacionesUseCase
{
    public function __construct(
        private NotificacionesService $notificacionesService
    ) {}

    public function ejecutar(ObtenerNotificacionesDTO $dto): array
    {
        return $this->notificacionesService->obtenerNotificaciones();
    }
}
