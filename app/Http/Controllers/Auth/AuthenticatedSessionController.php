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
        
        // Marcar que el usuario acaba de iniciar sesión
        session()->flash('just_logged_in', true);

        // Redirigir según el rol del usuario (SIN permitir rutas no autorizadas)
        $user = Auth::user();
        
        \Log::info('Login usuario', [
            'user_id' => $user->id,
            'roles_ids' => $user->roles_ids,
            'role' => $user->role,
            'role_name' => $user->role ? ($user->role->name ?? 'sin nombre') : 'null',
        ]);

        // Verificar primero si tiene rol Despacho en roles_ids
        $despachoRole = \App\Models\Role::where('name', 'Despacho')->first();
        if ($despachoRole) {
            // roles_ids puede ser string JSON o array directamente
            $rolesIds = is_array($user->roles_ids) 
                ? $user->roles_ids 
                : json_decode($user->roles_ids ?? '[]', true);
            
            if (in_array($despachoRole->id, $rolesIds)) {
                return redirect(route('despacho.index', absolute: false));
            }
        }

        // Verificar si tiene roles de bodega (Costura-Bodega o EPP-Bodega)
        $costruraRole = \App\Models\Role::where('name', 'Costura-Bodega')->first();
        $eppRole = \App\Models\Role::where('name', 'EPP-Bodega')->first();
        
        \Log::info('Bodega role check', [
            'costrura_role_exists' => $costruraRole ? $costruraRole->id : 'NULL',
            'epp_role_exists' => $eppRole ? $eppRole->id : 'NULL',
            'user_roles_ids' => $user->roles_ids,
        ]);
        
        if ($costruraRole || $eppRole) {
            $rolesIds = is_array($user->roles_ids) 
                ? $user->roles_ids 
                : json_decode($user->roles_ids ?? '[]', true);
            
            \Log::info('Bodega role check details', [
                'costrura_role_id' => $costruraRole ? $costruraRole->id : 'NULL',
                'epp_role_id' => $eppRole ? $eppRole->id : 'NULL',
                'parsed_roles_ids' => $rolesIds,
                'has_costrura' => $costruraRole ? in_array($costruraRole->id, $rolesIds) : false,
                'has_epp' => $eppRole ? in_array($eppRole->id, $rolesIds) : false,
            ]);
            
            if (($costruraRole && in_array($costruraRole->id, $rolesIds)) || 
                ($eppRole && in_array($eppRole->id, $rolesIds))) {
                \Log::info('Redirecting to bodega.pedidos');
                return redirect(route('gestion-bodega.pedidos', absolute: false));
            }
        }
        
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

            // Despacho - Módulo de despacho
            if ($roleName === 'Despacho' || $roleName === 'despacho') {
                return redirect(route('despacho.index', absolute: false));
            }
            
            // Insumos - Redirigir al login (no tiene acceso a asistencia personal)
            if ($roleName === 'insumos') {
                return redirect('/login')->with('error', 'Su rol no tiene acceso a esta área. Por favor contacte al administrador.');
            }

            // Patronista - Redirigir al login (no tiene acceso a asistencia personal)
            if ($roleName === 'patronista') {
                return redirect('/login')->with('error', 'Su rol no tiene acceso a esta área. Por favor contacte al administrador.');
            }

            // Aprobador de Cotizaciones - Cotizaciones pendientes
            if ($roleName === 'aprobador_cotizaciones') {
                return redirect(route('cotizaciones.pendientes', absolute: false));
            }

            // Supervisor de Pedidos - Supervisión de órdenes
            if ($roleName === 'supervisor_pedidos') {
                return redirect(route('supervisor-pedidos.index', absolute: false));
            }

            // Cartera - Aprobación/Rechazo de pedidos
            if ($roleName === 'cartera') {
                return redirect(route('cartera.pedidos', absolute: false));
            }

            // Bordado - Cartera de pedidos y cotizaciones
            if ($roleName === 'bordado') {
                return redirect(route('bordado.index', absolute: false));
            }

            // Supervisor de Asesores - Supervisión de asesores, cotizaciones y pedidos
            if ($roleName === 'supervisor_asesores') {
                return redirect(route('supervisor-asesores.dashboard', absolute: false));
            }

            // Cortador - Dashboard de operario
            if ($roleName === 'cortador') {
                return redirect(route('operario.dashboard', absolute: false));
            }

            // Costurero - Dashboard de operario
            if ($roleName === 'costurero') {
                return redirect(route('operario.dashboard', absolute: false));
            }

            // Bodeguero - Gestión de pedidos en bodega
            if ($roleName === 'bodeguero') {
                return redirect(route('gestion-bodega.pedidos', absolute: false));
            }

            // Costura-Reflectivo - Dashboard de operario
            if ($roleName === 'costura-reflectivo') {
                return redirect(route('operario.dashboard', absolute: false));
            }

            // Supervisor Personal - Gestión de asistencia
            if ($roleName === 'supervisor-personal') {
                return redirect(route('asistencia-personal.index', absolute: false));
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
