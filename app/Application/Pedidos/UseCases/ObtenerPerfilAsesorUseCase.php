<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerPerfilAsesorDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use Illuminate\Support\Facades\Auth;

/**
 * ObtenerPerfilAsesorUseCase
 * 
 * Use Case para obtener el perfil del asesor
 * Encapsula la lÃ³gica de obtenciÃ³n de datos del perfil
 */
class ObtenerPerfilAsesorUseCase
{
    use ManejaPedidosUseCase;

    public function ejecutar(ObtenerPerfilAsesorDTO $dto): mixed
    {
        $user = Auth::user();
        return $this->validarObjetoExiste($user, 'Usuario', 'autenticado');
    }
}

