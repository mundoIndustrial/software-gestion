<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    /**
     * Verificar si el usuario actual es admin
     */
    public static function isAdmin(): bool
    {
        return Auth::check() && 
               Auth::user()->role && 
               Auth::user()->role->name === 'admin';
    }

    /**
     * Verificar si el usuario actual es supervisor-admin
     */
    public static function isSupervisorAdmin(): bool
    {
        return Auth::check() && 
               Auth::user()->role && 
               Auth::user()->role->name === 'supervisor-admin';
    }

    /**
     * Verificar si el usuario puede ver insumos
     * Admin y supervisor-admin pueden ver insumos
     */
    public static function canViewInsumos(): bool
    {
        if (!Auth::check() || !Auth::user()->role) {
            return false;
        }

        $role = Auth::user()->role->name;
        return in_array($role, ['admin', 'supervisor-admin', 'insumos']);
    }

    /**
     * Verificar si el usuario puede ver producción
     * Admin y supervisor-admin pueden ver producción
     */
    public static function canViewProduccion(): bool
    {
        if (!Auth::check() || !Auth::user()->role) {
            return false;
        }

        $role = Auth::user()->role->name;
        return in_array($role, ['admin', 'supervisor-admin', 'supervisor']);
    }

    /**
     * Verificar si el usuario puede ver asesores
     * Admin y supervisor-admin pueden ver asesores
     */
    public static function canViewAsesores(): bool
    {
        if (!Auth::check() || !Auth::user()->role) {
            return false;
        }

        $role = Auth::user()->role->name;
        return in_array($role, ['admin', 'supervisor-admin', 'asesor']);
    }

    /**
     * Verificar si el usuario puede ver contador
     * Admin y supervisor-admin pueden ver contador
     */
    public static function canViewContador(): bool
    {
        if (!Auth::check() || !Auth::user()->role) {
            return false;
        }

        $role = Auth::user()->role->name;
        return in_array($role, ['admin', 'supervisor-admin', 'contador']);
    }

    /**
     * Verificar si el usuario puede ver usuarios
     * Solo admin puede ver usuarios
     */
    public static function canViewUsers(): bool
    {
        return self::isAdmin();
    }

    /**
     * Verificar si el usuario puede ver configuración
     * Solo admin puede ver configuración
     */
    public static function canViewConfig(): bool
    {
        return self::isAdmin();
    }

    /**
     * Verificar si el usuario puede ver cotizaciones
     * Admin y supervisor-admin pueden ver cotizaciones
     */
    public static function canViewCotizaciones(): bool
    {
        if (!Auth::check() || !Auth::user()->role) {
            return false;
        }

        $role = Auth::user()->role->name;
        return in_array($role, ['admin', 'supervisor-admin']);
    }
}
