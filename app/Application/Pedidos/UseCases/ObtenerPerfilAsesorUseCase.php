<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerPerfilAsesorDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use Illuminate\Support\Facades\Auth;

/**
 * ObtenerPerfilAsesorUseCase
 * Use Case para obtener el perfil del asesor
 * Encapsula la lógica de obtención de datos del perfil
 */
class ObtenerPerfilAsesorUseCase
{
    use ManejaPedidosUseCase;
    public function ejecutar(): mixed
    {
        $user = Auth::user();
        $this->validarObjetoExiste($user, 'Usuario', 'autenticado');
        return $user;
    }
}

