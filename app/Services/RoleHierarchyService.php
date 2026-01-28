<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Servicio de Jerarquía de Roles
 * 
 * Permite que ciertos roles hereden los permisos de otros roles.
 * Mantiene la lógica de herencia centralizada y reutilizable.
 */
class RoleHierarchyService
{
    /**
     * Obtiene todos los roles efectivos del usuario incluyendo roles heredados
     * 
     * @param array $userRoles - Array de roles actuales del usuario
     * @return array - Array con roles del usuario + roles heredados
     */
    public static function getEffectiveRoles(array $userRoles): array
    {
        $hierarchy = config('role-hierarchy.hierarchy', []);
        $effectiveRoles = $userRoles; // Comenzar con los roles actuales
        
        // Para cada rol del usuario, agregar todos sus roles heredados
        foreach ($userRoles as $role) {
            $inheritedRoles = self::getInheritedRoles($role, $hierarchy);
            foreach ($inheritedRoles as $inherited) {
                if (!in_array($inherited, $effectiveRoles)) {
                    $effectiveRoles[] = $inherited;
                }
            }
        }
        
        return $effectiveRoles;
    }
    
    /**
     * Obtiene recursivamente todos los roles que hereda un rol específico
     * 
     * @param string $role - Nombre del rol
     * @param array $hierarchy - Configuración de jerarquía
     * @param array $visited - Roles ya visitados (para evitar loops infinitos)
     * @return array - Array de roles heredados
     */
    private static function getInheritedRoles(string $role, array $hierarchy, array $visited = []): array
    {
        // Prevenir loops infinitos
        if (in_array($role, $visited)) {
            return [];
        }
        
        $visited[] = $role;
        $inherited = [];
        
        // Si el rol existe en la jerarquía
        if (isset($hierarchy[$role])) {
            $parentRoles = $hierarchy[$role];
            
            foreach ($parentRoles as $parentRole) {
                $inherited[] = $parentRole;
                // Recursivamente obtener roles del rol padre
                $grandParentRoles = self::getInheritedRoles($parentRole, $hierarchy, $visited);
                foreach ($grandParentRoles as $grandParent) {
                    if (!in_array($grandParent, $inherited)) {
                        $inherited[] = $grandParent;
                    }
                }
            }
        }
        
        return $inherited;
    }
    
    /**
     * Obtiene la cadena de jerarquía de un rol para logging
     * Útil para debug y auditoría
     * 
     * @param string $role - Nombre del rol
     * @return string - Representación en texto de la jerarquía
     */
    public static function getHierarchyChain(string $role): string
    {
        $hierarchy = config('role-hierarchy.hierarchy', []);
        
        if (!isset($hierarchy[$role])) {
            return $role;
        }
        
        $parents = implode(', ', $hierarchy[$role]);
        return "{$role} → [{$parents}]";
    }
}
