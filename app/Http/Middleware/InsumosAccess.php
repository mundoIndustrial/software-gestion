<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InsumosAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $user = Auth::user();

        // Admin puede ver todo, incluyendo insumos
        // Verificar tanto role (singular) como roles_ids (plural)
        if ($user->role && in_array($user->role->name, ['admin', 'supervisor-admin'])) {
            return $next($request);
        }

        // Verificar si el usuario tiene rol de insumos
        if ($user->hasRole('insumos')) {
            return $next($request);
        }

        return redirect('/')->with('error', 'No autorizado para acceder a este módulo.');
    }
}
