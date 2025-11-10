<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    public function index()
    {
        // Solo Admin puede acceder
        if (!auth()->user()->role || auth()->user()->role->name !== 'admin') {
            abort(403, 'Acción no autorizada.');
        }

        $users = User::with('role')->get();
        $roles = Role::all();
        return view('users.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        // Solo Admin puede crear usuarios
        if (!auth()->user()->role || auth()->user()->role->name !== 'admin') {
            abort(403, 'Acción no autorizada.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
        ]);

        return back()->with('status', 'Usuario creado correctamente');
    }

    public function update(Request $request, User $user)
    {
        // Solo Admin puede actualizar usuarios
        if (!auth()->user()->role || auth()->user()->role->name !== 'admin') {
            abort(403, 'Acción no autorizada.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
        ]);

        return back()->with('status', 'Usuario actualizado correctamente');
    }

    public function updatePassword(Request $request, User $user)
    {
        // Solo Admin puede cambiar contraseñas
        if (!auth()->user()->role || auth()->user()->role->name !== 'admin') {
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
        if (!auth()->user()->role || auth()->user()->role->name !== 'admin') {
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
