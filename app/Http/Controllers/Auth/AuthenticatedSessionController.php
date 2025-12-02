<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Redirigir según el rol del usuario
        $user = Auth::user();
        
        if ($user && $user->role) {
            $roleName = is_object($user->role) ? $user->role->name : $user->role;
            
            // Asesor - Dashboard de asesores
            if ($roleName === 'asesor') {
                return redirect()->intended(route('asesores.dashboard', absolute: false));
            }

            // Contador - Dashboard de contador
            if ($roleName === 'contador') {
                return redirect()->intended(route('contador.index', absolute: false));
            }

            // Supervisor - Gestión de órdenes
            if ($roleName === 'supervisor') {
                return redirect()->intended(route('registros.index', absolute: false));
            }

            // Supervisor Planta - Gestión de órdenes
            if ($roleName === 'supervisor_planta') {
                return redirect()->intended(route('registros.index', absolute: false));
            }
            
            // Insumos - Control de insumos
            if ($roleName === 'insumos') {
                return redirect()->intended(route('insumos.materiales.index', absolute: false));
            }

            // Aprobador de Cotizaciones - Cotizaciones pendientes
            if ($roleName === 'aprobador_cotizaciones') {
                return redirect()->intended(route('cotizaciones.pendientes', absolute: false));
            }
        }

        // Admin y otros - Dashboard principal
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
