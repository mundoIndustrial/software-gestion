<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerPerfilAsesorDTO;
use Illuminate\Support\Facades\Auth;

/**
 * ObtenerPerfilAsesorUseCase
 * 
 * Use Case para obtener el perfil del asesor
 * Encapsula la lógica de obtención de datos del perfil
 */
class ObtenerPerfilAsesorUseCase
{
    public function ejecutar(ObtenerPerfilAsesorDTO $dto): mixed
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('Por favor inicia sesión para ver tu perfil.');
        }

        return $user;
    }
}
