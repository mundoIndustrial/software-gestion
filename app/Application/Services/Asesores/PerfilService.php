<?php

namespace App\Application\Services\Asesores;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * PerfilService
 * 
 * Servicio para gestionar el perfil del asesor.
 * Encapsula la lógica de obtención y actualización del perfil.
 */
class PerfilService
{
    /**
     * Obtener datos del perfil actual
     */
    public function obtenerPerfil(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new \Exception('Usuario no autenticado', 401);
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telefono' => $user->telefono ?? null,
            'ciudad' => $user->ciudad ?? null,
            'departamento' => $user->departamento ?? null,
            'bio' => $user->bio ?? null,
            'avatar' => $user->avatar ?? null,
            'avatar_url' => $user->avatar ? asset('storage/avatars/' . $user->avatar) : null,
        ];
    }

    /**
     * Actualizar perfil del asesor
     */
    public function actualizarPerfil(array $datos, $archivoAvatar = null): array
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new \Exception('Usuario no autenticado', 401);
        }

        // Actualizar datos básicos
        $user->name = $datos['name'] ?? $user->name;
        $user->email = $datos['email'] ?? $user->email;
        $user->telefono = $datos['telefono'] ?? $user->telefono;
        $user->ciudad = $datos['ciudad'] ?? $user->ciudad;
        $user->departamento = $datos['departamento'] ?? $user->departamento;
        $user->bio = $datos['bio'] ?? $user->bio;

        // Actualizar contraseña si se proporciona
        if (!empty($datos['password'])) {
            $user->password = bcrypt($datos['password']);
        }

        // Manejar avatar
        if ($archivoAvatar) {
            $avatarUrl = $this->guardarAvatar($user, $archivoAvatar);
        } else {
            $avatarUrl = $user->avatar ? asset('storage/avatars/' . $user->avatar) : null;
        }

        $user->save();

        \Log::info('Perfil actualizado para usuario: ' . $user->name);

        return [
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'avatar_url' => $avatarUrl,
            'asesor' => $user->getNombreAsesor()
        ];
    }

    /**
     * Guardar archivo de avatar
     */
    private function guardarAvatar($user, $archivoAvatar): ?string
    {
        try {
            // Eliminar avatar anterior si existe
            if ($user->avatar && Storage::disk('public')->exists('avatars/' . $user->avatar)) {
                try {
                    Storage::disk('public')->delete('avatars/' . $user->avatar);
                    \Log::info('Avatar anterior eliminado: ' . $user->avatar);
                } catch (\Exception $e) {
                    \Log::warning('Error al eliminar avatar anterior: ' . $e->getMessage());
                }
            }

            // Crear directorio si no existe
            if (!Storage::disk('public')->exists('avatars')) {
                Storage::disk('public')->makeDirectory('avatars');
                \Log::info('Directorio de avatars creado');
            }

            // Generar nombre único
            $filename = time() . '_' . uniqid() . '.' . $archivoAvatar->getClientOriginalExtension();
            
            // Guardar archivo
            $path = $archivoAvatar->storeAs('avatars', $filename, 'public');
            
            if ($path) {
                $user->avatar = $filename;
                $avatarUrl = asset('storage/avatars/' . $filename);
                
                \Log::info('Avatar guardado exitosamente: ' . $filename);
                \Log::info('Avatar URL: ' . $avatarUrl);
                
                return $avatarUrl;
            } else {
                throw new \Exception('No se pudo guardar el archivo de avatar');
            }
        } catch (\Exception $e) {
            \Log::error('Error al guardar avatar: ' . $e->getMessage());
            throw new \Exception('Error al guardar la imagen: ' . $e->getMessage());
        }
    }
}
