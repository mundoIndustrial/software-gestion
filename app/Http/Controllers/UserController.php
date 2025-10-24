<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        // Solo Admin puede acceder
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Acción no autorizada.');
        }

        $users = User::all();
        return view('users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user)
    {
        // Solo Admin puede cambiar roles
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Acción no autorizada.');
        }

        $request->validate([
            'role' => 'required|in:admin,Operador'
        ]);

        $user->role = $request->role;
        $user->save();

        return back()->with('status', 'Rol actualizado correctamente');
    }
}
