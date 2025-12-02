<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Services\SecurityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

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
            'password' => ['required', 'string', 'min:8'],
            'roles_ids' => ['required', 'array'],
            'roles_ids.*' => ['exists:roles,id'],
        ]);

        $rolesIds = array_map('intval', $request->roles_ids ?? []);
        
        \Log::info('Roles mapeados', [
            'roles_ids' => $rolesIds,
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'roles_ids' => $rolesIds,
        ]);

        // Registrar creación de usuario
        SecurityLogger::logUserCreation($user->id);

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

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'roles_ids' => $newRoles,
        ]);

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
}
