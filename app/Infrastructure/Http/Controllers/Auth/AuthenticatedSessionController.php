<?php

namespace App\Infrastructure\Http\Controllers\Auth;

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
        \Log::info('=== AuthenticatedSessionController::store() INICIADO ===');
        
        $request->authenticate();

        $request->session()->regenerate();
        
        // Marcar que el usuario acaba de iniciar sesión
        session()->flash('just_logged_in', true);

        // Redirigir según el rol del usuario (SIN permitir rutas no autorizadas)
        $user = Auth::user();
        
        \Log::info('Usuario después de authenticate', [
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);
        
        // Recargar el usuario para asegurar que tiene los datos más recientes
        $user = \App\Models\User::find($user->id);
        
        \Log::info('Usuario después de find', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'roles_ids' => json_encode($user->roles_ids ?? []),
            'roles_ids_type' => gettype($user->roles_ids),
        ]);

        if ($user && $user->hasRole('visualizador_recibos_logo')) {
            \Log::info('→ Redirigiendo a registros.recibos-bordado-estampado');
            return redirect(route('registros.recibos-bordado-estampado', absolute: false));
        }

        if ($user && ($user->hasRole('diseñador-logos') || $user->hasRole('bordador'))) {
            \Log::info('→ Redirigiendo a visualizador-logo.pedidos-logo');
            return redirect(route('visualizador-logo.pedidos-logo', absolute: false));
        }

        if ($user && $user->hasRole('lider-control-calidad')) {
            \Log::info('→ Redirigiendo a control-calidad.dashboard');
            return redirect(route('control-calidad.dashboard', absolute: false));
        }

        // Gestor EPP - Solo acceso a gestión de EPPs
        if ($user && $user->hasRole('gestor_epp')) {
            \Log::info('→ Redirigiendo a epp.gestion');
            return redirect(route('epp.gestion', absolute: false));
        }
        
        // Gestor Lavandería - Solo acceso a gestión de lavandería
        \Log::info('=== VERIFICANDO ROL GESTOR-LAVANDERIA ===', [
            'user_id' => $user->id,
            'roles_ids' => $user->roles_ids,
            'has_role_result' => $user->hasRole('gestor-lavanderia'),
            'roles_collection' => $user->roles->pluck('name')->toArray(),
        ]);
        
        if ($user && $user->hasRole('gestor-lavanderia')) {
            \Log::info('=== REDIRIGIENDO A GESTION-LAVANDERIA ===');
            return redirect(route('gestion-lavanderia.index', absolute: false));
        }
        
        \Log::info('Login usuario - Roles check', [
            'user_id' => $user->id,
            'roles_ids' => $user->roles_ids,
            'role' => $user->role,
            'role_name' => $user->role ? ($user->role->name ?? 'sin nombre') : 'null',
            'has_gestor_lavanderia' => $user->hasRole('gestor-lavanderia'),
        ]);

        // Verificar primero si tiene rol Despacho en roles_ids
        $despachoRole = \App\Models\Role::where('name', 'Despacho')->first();
        if ($despachoRole) {
            // roles_ids puede ser string JSON o array directamente
            $rolesIds = is_array($user->roles_ids) 
                ? $user->roles_ids 
                : json_decode($user->roles_ids ?? '[]', true);
            
            if (in_array($despachoRole->id, $rolesIds)) {
                \Log::info('→ Redirigiendo a despacho.index');
                return redirect(route('despacho.index', absolute: false));
            }
        }

        // Verificar si tiene roles de bodega (Costura-Bodega o EPP-Bodega)
        $costruraRole = \App\Models\Role::where('name', 'Costura-Bodega')->first();
        $eppRole = \App\Models\Role::where('name', 'EPP-Bodega')->first();
        
        \Log::info('Bodega role check', [
            'costrura_role_exists' => $costruraRole ? $costruraRole->id : 'NULL',
            'epp_role_exists' => $eppRole ? $eppRole->id : 'NULL',
            'user_roles_ids' => json_encode($user->roles_ids ?? []),
        ]);
        
        if ($costruraRole || $eppRole) {
            $rolesIds = is_array($user->roles_ids) 
                ? $user->roles_ids 
                : json_decode($user->roles_ids ?? '[]', true);
            
            \Log::info('Bodega role check details', [
                'costrura_role_id' => $costruraRole ? $costruraRole->id : 'NULL',
                'epp_role_id' => $eppRole ? $eppRole->id : 'NULL',
                'parsed_roles_ids' => json_encode($rolesIds),
                'has_costrura' => $costruraRole ? in_array($costruraRole->id, $rolesIds) : false,
                'has_epp' => $eppRole ? in_array($eppRole->id, $rolesIds) : false,
            ]);
            
            if (($costruraRole && in_array($costruraRole->id, $rolesIds)) || 
                ($eppRole && in_array($eppRole->id, $rolesIds))) {
                \Log::info('→ Redirigiendo a gestion-bodega.pedidos');
                return redirect(route('gestion-bodega.pedidos', absolute: false));
            }
        }
        
        // Si tiene role principal, usar el dashboard
        \Log::info('→ Redirigiendo a dashboard (role-based)');
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

            // Confección Sobremedida - Dashboard de operario
            if ($roleName === 'confeccion-sobremedida') {
                return redirect(route('operario.dashboard', absolute: false));
            }

            // Bodeguero - Gestión de pedidos en bodega
            if ($roleName === 'bodeguero') {
                return redirect(route('gestion-bodega.pedidos', absolute: false));
            }

            // Gestion Bodega - Recibos de bodega
            if ($roleName === 'gestion-bodega') {
                return redirect(route('registros.recibos-bodega', absolute: false));
            }

            // Costura-Reflectivo - Dashboard de operario
            if ($roleName === 'costura-reflectivo') {
                return redirect(route('operario.dashboard', absolute: false));
            }

            // Lider-Reflectivo - Dashboard de operario
            if ($roleName === 'lider-reflectivo') {
                return redirect(route('operario.dashboard', absolute: false));
            }

            // Vista Costura - Dashboard de operario
            if ($roleName === 'vista-costura') {
                return redirect(route('operario.dashboard', absolute: false));
            }

            // Administrador Costura - Dashboard de operario
            if ($roleName === 'administrador-costura') {
                return redirect(route('operario.dashboard', absolute: false));
            }

            // Supervisor Personal - Gestión de asistencia
            if ($roleName === 'supervisor-personal') {
                return redirect(route('asistencia-personal.index', absolute: false));
            }
        }

        // Fallback: Si no hay rol definido o no coincide con ninguno, redirigir al home
        // Nunca redirigir a /dashboard si el usuario no tiene un rol específico asignado
        return redirect('/');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Hacer logout
        Auth::guard('web')->logout();

        // Invalidar sesión
        $request->session()->invalidate();

        // Regenerar token para la próxima sesión
        $request->session()->regenerateToken();

        // Redirigir al login
        return redirect(route('login'))->with('success', 'Sesión cerrada correctamente');
    }
}
