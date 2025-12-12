<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Redirigir a Google para autenticación
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Manejar callback de Google
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Buscar usuario por email
            $user = User::where('email', $googleUser->getEmail())->first();
            
            // Si el usuario NO existe, mostrar error
            if (!$user) {
                return redirect('/login')->with('error', '❌ No puedes ingresar. Por favor, habla con el administrador del sitio para que cree tu cuenta.');
            }
            
            // Actualizar google_id si no existe
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
            
            // Autenticar usuario
            Auth::login($user, remember: true);
            
            // Redirigir según rol
            return $this->redirectByRole($user);
            
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Error al autenticar con Google: ' . $e->getMessage());
        }
    }

    /**
     * Redirigir según el rol del usuario
     */
    private function redirectByRole($user)
    {
        if ($user && $user->role) {
            $roleName = is_object($user->role) ? $user->role->name : $user->role;
            
            if ($roleName === 'asesor') {
                return redirect()->intended(route('asesores.dashboard'));
            }
            
            if ($roleName === 'contador') {
                return redirect()->intended(route('contador.index'));
            }
            
            if ($roleName === 'supervisor') {
                return redirect()->intended(route('registros.index'));
            }
            
            if ($roleName === 'supervisor_planta') {
                return redirect()->intended(route('registros.index'));
            }
            
            if ($roleName === 'insumos') {
                return redirect()->intended(route('insumos.materiales.index'));
            }
            
            if ($roleName === 'patronista') {
                return redirect()->intended(route('insumos.materiales.index'));
            }
            
            if ($roleName === 'aprobador_cotizaciones') {
                return redirect()->intended(route('cotizaciones.pendientes'));
            }
            
            if ($roleName === 'supervisor_pedidos') {
                return redirect()->intended(route('supervisor-pedidos.index'));
            }
        }
        
        return redirect()->intended(route('dashboard'));
    }
}
