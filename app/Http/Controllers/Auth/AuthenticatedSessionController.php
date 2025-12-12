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

        // Redirigir según el rol del usuario (SIN permitir rutas no autorizadas)
        $user = Auth::user();
        
        \Log::info('Login usuario', [
            'user_id' => $user->id,
            'roles_ids' => $user->roles_ids,
            'role' => $user->role,
            'role_name' => $user->role ? ($user->role->name ?? 'sin nombre') : 'null',
        ]);
        
        if ($user && $user->role) {
            $roleName = is_object($user->role) ? $user->role->name : $user->role;
            
            \Log::info('Rol detectado', ['roleName' => $roleName]);
            
            // Asesor - Dashboard de asesores
            if ($roleName === 'asesor') {
                return redirect(route('asesores.dashboard', absolute: false));
            }

            // Contador - Dashboard de contador
            if ($roleName === 'contador') {
                return redirect(route('contador.index', absolute: false));
            }

            // Supervisor - Gestión de órdenes
            if ($roleName === 'supervisor') {
                return redirect(route('registros.index', absolute: false));
            }

            // Supervisor Planta - Gestión de órdenes
            if ($roleName === 'supervisor_planta') {
                return redirect(route('registros.index', absolute: false));
            }
            
            // Insumos - Control de insumos
            if ($roleName === 'insumos') {
                return redirect(route('insumos.materiales.index', absolute: false));
            }

            // Patronista - Control de insumos (solo lectura)
            if ($roleName === 'patronista') {
                return redirect(route('insumos.materiales.index', absolute: false));
            }

            // Aprobador de Cotizaciones - Cotizaciones pendientes
            if ($roleName === 'aprobador_cotizaciones') {
                return redirect(route('cotizaciones.pendientes', absolute: false));
            }

            // Supervisor de Pedidos - Supervisión de órdenes
            if ($roleName === 'supervisor_pedidos') {
                return redirect(route('supervisor-pedidos.index', absolute: false));
            }

            // Cortador - Dashboard de operario
            if ($roleName === 'cortador') {
                return redirect(route('operario.dashboard', absolute: false));
            }

            // Costurero - Dashboard de operario
            if ($roleName === 'costurero') {
                return redirect(route('operario.dashboard', absolute: false));
            }
        }

        // Admin y otros - Dashboard principal
        return redirect(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Regenerar token ANTES de invalidar la sesión
        // Esto previene el error 419 si la sesión está a punto de expirar
        $request->session()->regenerateToken();

        // Hacer logout
        Auth::guard('web')->logout();

        // Invalidar sesión
        $request->session()->invalidate();

        // Redirigir con mensaje de éxito
        return redirect('/')->with('success', 'Sesión cerrada correctamente');
    }
}
