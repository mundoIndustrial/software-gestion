<?php

namespace App\Infrastructure\Http\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait para validar acceso de solo lectura en controladores
 * Centraliza la validación de roles read-only
 */
trait ValidateReadOnlyAccess
{
    /**
     * Validar que el usuario no sea de solo lectura
     * Retorna JsonResponse con error 403 si el usuario es read-only
     * 
     * @return JsonResponse|null null si el acceso es permitido, JsonResponse con error si es denegado
     */
    protected function validateNotReadOnly(): ?JsonResponse
    {
        $rolesDelUsuario = auth()->user()->getRoleNames()->toArray();
        
        if ($this->roleService->esReadOnly($rolesDelUsuario)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción. Tu rol es de solo lectura.'
            ], 403);
        }
        
        return null;
    }

    /**
     * Obtener el estado isReadOnly del usuario actual
     * 
     * @return bool true si el usuario es de solo lectura
     */
    protected function isReadOnly(): bool
    {
        $rolesDelUsuario = auth()->user()->getRoleNames()->toArray();
        return $this->roleService->esReadOnly($rolesDelUsuario);
    }

    /**
     * Obtener roles del usuario actual
     * 
     * @return array Array de roles del usuario
     */
    protected function getUserRoles(): array
    {
        return auth()->user()->getRoleNames()->toArray();
    }
}
