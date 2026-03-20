<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\UpdateProfileRequest;
use App\Application\SupervisorPedidos\DTOs\UpdateProfileResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UpdateProfileUseCase
{
    public function execute(UpdateProfileRequest $request): UpdateProfileResponse
    {
        try {
            DB::beginTransaction();

            // Obtener usuario
            $user = User::findOrFail($request->getUserId());

            Log::info('Iniciando actualización de perfil', [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);

            // Actualizar datos básicos
            $user->name = $request->getName();
            $user->email = $request->getEmail();
            
            if ($request->getTelefono() !== null) {
                $user->telefono = $request->getTelefono();
            }
            
            if ($request->getCiudad() !== null) {
                $user->ciudad = $request->getCiudad();
            }
            
            if ($request->getDepartamento() !== null) {
                $user->departamento = $request->getDepartamento();
            }
            
            if ($request->getBio() !== null) {
                $user->bio = $request->getBio();
            }

            // Actualizar contraseña si se proporciona
            if (!empty($request->getPassword())) {
                $user->password = bcrypt($request->getPassword());
                Log::info('Contraseña actualizada para usuario', ['user_id' => $user->id]);
            }

            // Manejar avatar
            if ($request->hasAvatarFile()) {
                $this->handleAvatarUpload($user, $request->getAvatarFile());
            }

            // Guardar cambios
            $user->save();

            DB::commit();

            Log::info('Perfil actualizado correctamente', [
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);

            return new UpdateProfileResponse(
                true,
                'Perfil actualizado correctamente',
                [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar
                ]
            );

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar perfil: ' . $e->getMessage(), [
                'user_id' => $request->getUserId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleAvatarUpload(User $user, $file): void
    {
        try {
            // Eliminar avatar anterior si existe
            if ($user->avatar && Storage::disk('public')->exists('supervisores/' . $user->avatar)) {
                try {
                    Storage::disk('public')->delete('supervisores/' . $user->avatar);
                    Log::info('Avatar anterior eliminado', [
                        'user_id' => $user->id,
                        'avatar_old' => $user->avatar
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Error al eliminar avatar anterior: ' . $e->getMessage());
                }
            }

            // Crear directorio si no existe
            if (!Storage::disk('public')->exists('supervisores')) {
                Storage::disk('public')->makeDirectory('supervisores');
                Log::info('Directorio de supervisores creado');
            }

            // Convertir a webp y guardar
            $filename = time() . '_' . uniqid() . '.webp';

            $image = \Intervention\Image\ImageManager::gd()
                ->read($file)
                ->scaleDown(height: 500)
                ->toWebp();

            Storage::disk('public')->put('supervisores/' . $filename, $image);
            $user->avatar = $filename;

            Log::info('Avatar guardado como webp', [
                'user_id' => $user->id,
                'filename' => $filename
            ]);

        } catch (\Exception $e) {
            Log::error('Error al procesar avatar: ' . $e->getMessage());
            throw new \RuntimeException('Error al procesar la imagen: ' . $e->getMessage());
        }
    }
}
