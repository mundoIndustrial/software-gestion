<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Services\SecurityLogger;
use App\Services\GoogleTestUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class UserController extends Controller
{
    public function index()
    {
        // Solo Admin puede acceder
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Acción no autorizada.');
        }

        $users = User::all();
        $roles = Role::all();
        return view('users.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        // Solo Admin puede crear usuarios
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Acción no autorizada.');
        }

        // Log para debugging
        \Log::info('Creando usuario', [
            'name' => $request->name,
            'email' => $request->email,
            'roles_ids_request' => $request->roles_ids,
        ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'password' => ['required', 'string', 'min:8'],
            'roles_ids' => ['required', 'array'],
            'roles_ids.*' => ['exists:roles,id'],
        ]);

        $rolesIds = array_map('intval', $request->roles_ids ?? []);
        
        \Log::info('Roles mapeados', [
            'roles_ids' => $rolesIds,
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $this->processAvatar($request->file('avatar'));
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'avatar' => $avatarPath,
            'password' => Hash::make($request->password),
            'roles_ids' => $rolesIds,
        ]);

        // Registrar creación de usuario
        SecurityLogger::logUserCreation($user->id);

        // Agregar a usuarios de prueba de Google
        GoogleTestUserService::addTestUser($user->email);

        return back()->with('status', 'Usuario creado correctamente');
    }

    public function update(Request $request, User $user)
    {
        // Solo Admin puede actualizar usuarios
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Acción no autorizada.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'telefono' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'],
            'roles_ids' => ['required', 'array'],
            'roles_ids.*' => ['exists:roles,id'],
        ]);

        // Log para debugging
        \Log::info('Actualizando usuario', [
            'user_id' => $user->id,
            'roles_ids_request' => $request->roles_ids,
            'roles_ids_mapped' => array_map('intval', $request->roles_ids ?? []),
        ]);

        // Guardar roles antiguos para auditoría
        $oldRoles = $user->roles_ids;
        $newRoles = array_map('intval', $request->roles_ids ?? []);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'roles_ids' => $newRoles,
        ];

        // Procesar avatar si se proporciona
        if ($request->hasFile('avatar')) {
            // Eliminar avatar anterior si existe
            if ($user->avatar) {
                $oldAvatarPath = 'avatars/' . $user->avatar;
                if (Storage::disk('public')->exists($oldAvatarPath)) {
                    Storage::disk('public')->delete($oldAvatarPath);
                    \Log::info('Avatar anterior eliminado', ['path' => $oldAvatarPath]);
                }
            }
            $avatarPath = $this->processAvatar($request->file('avatar'));
            if ($avatarPath) {
                $updateData['avatar'] = $avatarPath;
            }
        }

        $user->update($updateData);

        // Verificar que se guardó
        $user->refresh();
        \Log::info('Usuario actualizado', [
            'user_id' => $user->id,
            'roles_ids_guardado' => $user->roles_ids,
        ]);

        // Registrar cambio de roles si es diferente
        if ($oldRoles !== $newRoles) {
            SecurityLogger::logRoleChange($user->id, $oldRoles, $newRoles);
        }

        return back()->with('status', 'Usuario actualizado correctamente');
    }

    public function updatePassword(Request $request, User $user)
    {
        // Solo Admin puede cambiar contraseñas
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Acción no autorizada.');
        }

        $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('status', 'Contraseña actualizada correctamente');
    }

    public function show(User $user)
    {
        // Solo Admin puede ver usuarios
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Acción no autorizada.');
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'telefono' => $user->telefono,
            'avatar' => $user->avatar ? asset('storage/avatars/' . $user->avatar) : null,
            'roles_ids' => $user->roles_ids ?? [],
        ]);
    }

    public function destroy(User $user)
    {
        // Solo Admin puede eliminar usuarios
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Acción no autorizada.');
        }

        // No permitir eliminar el propio usuario
        if ($user->id === auth()->user()->id) {
            return back()->with('error', 'No puedes eliminar tu propio usuario');
        }

        $user->delete();

        return back()->with('status', 'Usuario eliminado correctamente');
    }

    /**
     * Procesa y guarda un avatar en formato WebP
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @return string|null Nombre del archivo guardado
     */
    private function processAvatar($file)
    {
        try {
            // Usar Intervention Image de forma compatible
            $image = ImageManager::gd()
                ->read($file->getPathname());
            
            // Redimensionar a 300x300 si es necesario
            if ($image->width() > 300 || $image->height() > 300) {
                $image->scale(width: 300, height: 300);
            }
            
            // Generar nombre único CON EXTENSIÓN .webp
            $filename = 'avatar_' . time() . '_' . uniqid() . '.webp';
            
            // Crear directorio si no existe
            $directory = 'avatars';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            
            // Codificar a WebP con calidad 80
            $encoded = $image->encodeByMediaType('image/webp', quality: 80);
            $path = $directory . '/' . $filename;
            
            // Guardar el contenido
            Storage::disk('public')->put($path, (string)$encoded);
            
            // Verificación
            $size = Storage::disk('public')->size($path);
            \Log::info('Avatar guardado como WebP', [
                'filename' => $filename,
                'path' => $path,
                'size' => $size . ' bytes',
            ]);
            
            return $filename;
        } catch (\Exception $e) {
            \Log::error('Error al procesar avatar: ' . $e->getMessage(), [
                'original_name' => $file->getClientOriginalName(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}


