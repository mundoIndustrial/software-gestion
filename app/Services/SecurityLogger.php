<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SecurityLogger
{
    /**
     * Registrar cambio de roles
     */
    public static function logRoleChange($userId, $oldRoles, $newRoles, $changedBy = null)
    {
        $changedBy = $changedBy ?? Auth::id();
        
        Log::channel('security')->info('Cambio de roles detectado', [
            'timestamp' => now(),
            'user_id' => $userId,
            'changed_by' => $changedBy,
            'old_roles' => $oldRoles,
            'new_roles' => $newRoles,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Registrar intento de acceso no autorizado
     */
    public static function logUnauthorizedAccess($userId, $requiredRole, $route)
    {
        Log::channel('security')->warning('Intento de acceso no autorizado', [
            'timestamp' => now(),
            'user_id' => $userId,
            'required_role' => $requiredRole,
            'route' => $route,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Registrar cambio de contraseña
     */
    public static function logPasswordChange($userId, $changedBy = null)
    {
        $changedBy = $changedBy ?? Auth::id();
        
        Log::channel('security')->info('Cambio de contraseña detectado', [
            'timestamp' => now(),
            'user_id' => $userId,
            'changed_by' => $changedBy,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Registrar login exitoso
     */
    public static function logSuccessfulLogin($userId)
    {
        Log::channel('security')->info('Login exitoso', [
            'timestamp' => now(),
            'user_id' => $userId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Registrar intento de login fallido
     */
    public static function logFailedLogin($email)
    {
        Log::channel('security')->warning('Intento de login fallido', [
            'timestamp' => now(),
            'email' => $email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Registrar eliminación de usuario
     */
    public static function logUserDeletion($userId, $deletedBy = null)
    {
        $deletedBy = $deletedBy ?? Auth::id();
        
        Log::channel('security')->warning('Usuario eliminado', [
            'timestamp' => now(),
            'user_id' => $userId,
            'deleted_by' => $deletedBy,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Registrar creación de usuario
     */
    public static function logUserCreation($userId, $createdBy = null)
    {
        $createdBy = $createdBy ?? Auth::id();
        
        Log::channel('security')->info('Usuario creado', [
            'timestamp' => now(),
            'user_id' => $userId,
            'created_by' => $createdBy,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
