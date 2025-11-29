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
        if (Auth::check()) {
            $user = Auth::user();
            // Verificar si el usuario tiene rol de insumos
            $isInsumos = $user->role === 'insumos' || 
                        (is_object($user->role) && $user->role->name === 'insumos');
            
            if ($isInsumos) {
                return $next($request);
            }
        }

        return redirect('/')->with('error', 'No autorizado para acceder a este módulo.');
    }
}
