<?php

namespace App\Helpers;

use App\Services\RoleHierarchyService;

/**
 * Helper para verificar roles considerando jerarquía
 * 
 * Uso:
 *   hasRole('asesor') - Retorna true si el usuario tiene 'asesor' o hereda de él
 *   hasAnyRole(['asesor', 'admin']) - Retorna true si tiene cualquiera de esos roles
 *   getAllRoles() - Retorna todos los roles (incluyendo heredados)
 */
class RoleHelper
{
    /**
     * Obtiene todos los roles efectivos del usuario autenticado (con herencia)
     * 
     * @return array
     */
    public static function getAllRoles(): array
    {
        $user = \Auth::user();
        if (!$user) {
            return [];
        }
        
        $roles = $user->roles()->pluck('name')->toArray();
        return RoleHierarchyService::getEffectiveRoles($roles);
    }
    
    /**
     * Verifica si el usuario actual tiene un rol específico (considerando herencia)
     * 
     * @param string $roleName
     * @return bool
     */
    public static function hasRole(string $roleName): bool
    {
        return in_array($roleName, self::getAllRoles());
    }
    
    /**
     * Verifica si el usuario actual tiene alguno de varios roles (considerando herencia)
     * 
     * @param array $roleNames
     * @return bool
     */
    public static function hasAnyRole(array $roleNames): bool
    {
        $userRoles = self::getAllRoles();
        
        foreach ($roleNames as $role) {
            if (in_array($role, $userRoles)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Verifica si el usuario actual tiene TODOS los roles especificados (considerando herencia)
     * 
     * @param array $roleNames
     * @return bool
     */
    public static function hasAllRoles(array $roleNames): bool
    {
        $userRoles = self::getAllRoles();
        
        foreach ($roleNames as $role) {
            if (!in_array($role, $userRoles)) {
                return false;
            }
        }
        
        return true;
    }
}
