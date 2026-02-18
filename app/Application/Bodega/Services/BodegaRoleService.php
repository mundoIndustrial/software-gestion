<?php

namespace App\Application\Bodega\Services;

class BodegaRoleService
{
    /**
     * Obtener áreas permitidas según los roles del usuario
     */
    public function obtenerAreasPermitidas(array $rolesDelUsuario): array
    {
        $areasPermitidas = [];
        
        if (in_array('Costura-Bodega', $rolesDelUsuario)) {
            $areasPermitidas[] = 'Costura';
        }
        
        if (in_array('EPP-Bodega', $rolesDelUsuario)) {
            $areasPermitidas[] = 'EPP';
        }
        
        // Si no tiene roles específicos de área, ver todas las áreas
        if (empty($areasPermitidas)) {
            $areasPermitidas = ['Costura', 'EPP', 'Otro', null];
        }
        
        return $areasPermitidas;
    }

    /**
     * Obtener la clase del modelo de detalles según el rol del usuario
     */
    public function getDetallesModelClass(array $rolesDelUsuario): string
    {
        if (in_array('EPP-Bodega', $rolesDelUsuario)) {
            return \App\Models\EppBodegaDetalle::class;
        } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
            return \App\Models\CosturaBodegaDetalle::class;
        }
        
        // Por defecto: Bodeguero
        return \App\Models\BodegaDetallesTalla::class;
    }

    /**
     * Obtener instancia del modelo de detalles según el rol del usuario
     */
    public function getDetallesModel(array $rolesDelUsuario)
    {
        $modelClass = $this->getDetallesModelClass($rolesDelUsuario);
        return app($modelClass);
    }

    /**
     * Determinar si el usuario es de solo lectura (solo EPP o Costura)
     * Nota: despacho SÍ puede guardar notas
     */
    public function esReadOnly(array $rolesDelUsuario): bool
    {
        return in_array('Costura-Bodega', $rolesDelUsuario) 
            || in_array('EPP-Bodega', $rolesDelUsuario);
        // despacho se removió - ahora puede guardar notas
    }

    /**
     * Determinar el rol actual del usuario para notas
     */
    public function determinarRolActual(array $roleNames): string
    {
        if (in_array('admin', $roleNames)) {
            return 'admin';
        } elseif (in_array('despacho', $roleNames)) {
            return 'despacho';
        } elseif (in_array('Costura-Bodega', $roleNames)) {
            return 'Costura-Bodega';
        } elseif (in_array('EPP-Bodega', $roleNames)) {
            return 'EPP-Bodega';
        }
        
        return 'Bodeguero';
    }

    /**
     * Validar si un usuario puede acceder a un área específica
     */
    public function puedeAccederArea(array $rolesDelUsuario, string $area): bool
    {
        $areasPermitidas = $this->obtenerAreasPermitidas($rolesDelUsuario);
        return in_array($area, $areasPermitidas) || in_array(null, $areasPermitidas);
    }

    /**
     * Obtener el modelo de auditoría según el rol
     */
    public function getAuditoriaModelClass(array $rolesDelUsuario): string
    {
        if (in_array('EPP-Bodega', $rolesDelUsuario)) {
            return \App\Models\EppBodegaAuditoria::class;
        } elseif (in_array('Costura-Bodega', $rolesDelUsuario)) {
            return \App\Models\CosturaBodegaAuditoria::class;
        }
        
        return \App\Models\BodegaAuditoria::class;
    }
}
