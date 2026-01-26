<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ActualizarPerfilAsesorDTO;
use App\Application\Services\Asesores\PerfilService;

/**
 * ActualizarPerfilAsesorUseCase
 * 
 * Use Case para actualizar el perfil del asesor
 * Encapsula la lógica de validación y actualizacion de datos
 */
class ActualizarPerfilAsesorUseCase
{
    public function __construct(
        private PerfilService $perfilService
    ) {}

    public function ejecutar(ActualizarPerfilAsesorDTO $dto): array
    {
        $validated = [
            'name' => $dto->nombre,
            'email' => $dto->email,
            'telefono' => $dto->telefono,
            'ciudad' => $dto->ciudad,
            'departamento' => $dto->departamento,
            'bio' => $dto->bio,
        ];

        if ($dto->password) {
            $validated['password'] = $dto->password;
        }

        return $this->perfilService->actualizarPerfil($validated, $dto->avatar);
    }
}

