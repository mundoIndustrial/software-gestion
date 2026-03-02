<?php

namespace App\Application\Services\Asesores;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

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
     * Guardar archivo de avatar convertido a WebP
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

            // Usar Intervention Image de forma compatible
            $image = ImageManager::gd()
                ->read($archivoAvatar->getPathname());
            
            // Redimensionar a 300x300 si es necesario
            if ($image->width() > 300 || $image->height() > 300) {
                $image->scale(width: 300, height: 300);
            }
            
            // Generar nombre único CON EXTENSIÓN .webp
            $filename = 'avatar_' . time() . '_' . uniqid() . '.webp';
            
            // Codificar a WebP con calidad 80
            $encoded = $image->encodeByMediaType('image/webp', quality: 80);
            
            // Guardar en storage
            $path = 'avatars/' . $filename;
            Storage::disk('public')->put($path, (string)$encoded);
            
            // Verificar que se guardó
            $size = Storage::disk('public')->size($path);
            
            if ($size > 0) {
                $user->avatar = $filename;
                $avatarUrl = asset('storage/avatars/' . $filename);
                
                \Log::info('Avatar guardado exitosamente en WebP', [
                    'filename' => $filename,
                    'size' => $size . ' bytes',
                    'url' => $avatarUrl,
                ]);
                
                return $avatarUrl;
            } else {
                throw new \Exception('El archivo guardado está vacío');
            }
        } catch (\Exception $e) {
            \Log::error('Error al guardar avatar: ' . $e->getMessage(), [
                'original_name' => $archivoAvatar?->getClientOriginalName(),
                'exception' => $e->getMessage(),
            ]);
            throw new \Exception('Error al guardar la imagen: ' . $e->getMessage());
        }
    }
}
