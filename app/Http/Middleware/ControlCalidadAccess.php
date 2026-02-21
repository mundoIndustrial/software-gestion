<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ControlCalidadAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $usuario = auth()->user();

        if (!$usuario->hasRole('control de calidad')) {
            return redirect()->route('login')
                ->with('error', 'No tienes acceso a esta sección');
        }

        return $next($request);
    }
}
