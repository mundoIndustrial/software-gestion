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

        $user = auth()->user();
        
        // Redireccionar según el rol del usuario
        if ($user->role) {
            // Si es un objeto Role
            if (is_object($user->role)) {
                if ($user->role->name === 'supervisor') {
                    return redirect()->intended(route('registros.index', absolute: false));
                }
                if ($user->role->name === 'supervisor_planta') {
                    return redirect()->intended(route('dashboard', absolute: false));
                }
                if ($user->role->name === 'insumos') {
                    return redirect()->intended(route('insumos.materiales.index', absolute: false));
                }
            } else {
                // Si es string directo
                if ($user->role === 'supervisor') {
                    return redirect()->intended(route('registros.index', absolute: false));
                }
                if ($user->role === 'supervisor_planta') {
                    return redirect()->intended(route('dashboard', absolute: false));
                }
                if ($user->role === 'insumos') {
                    return redirect()->intended(route('insumos.materiales.index', absolute: false));
                }
            }
        }

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
