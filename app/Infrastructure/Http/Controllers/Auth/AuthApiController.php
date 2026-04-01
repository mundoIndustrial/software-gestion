<?php

namespace App\Infrastructure\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthApiController extends Controller
{
    public function csrf(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'CSRF token generado correctamente',
            'data' => [
                'csrf_token' => $request->session()->token(),
            ],
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $remember = (bool) ($credentials['remember'] ?? false);

        if (!Auth::guard('web')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $remember)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user();

        // Determinar la redirección según el rol del usuario
        $redirectTo = '/dashboard';
        if ($user->hasRole('revisor_entregas')) {
            $redirectTo = '/recibos-costura';
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesion iniciada correctamente',
            'data' => [
                'redirect_to' => $redirectTo,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values()->toArray(),
                ],
            ],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        // Determinar la redirección según el rol del usuario
        $redirectTo = '/dashboard';
        if ($user->hasRole('revisor_entregas')) {
            $redirectTo = '/recibos-costura';
        }

        return response()->json([
            'success' => true,
            'message' => 'Cuenta creada correctamente',
            'data' => [
                'redirect_to' => $redirectTo,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values()->toArray(),
                ],
            ],
        ], 201);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink([
            'email' => $validated['email'],
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __($status),
            'data' => null,
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function (User $user) use ($validated) {
                $user->forceFill([
                    'password' => Hash::make($validated['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => __($status),
            'data' => null,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        if (!Auth::guard('web')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
                'data' => null,
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Usuario autenticado',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames()->values()->toArray(),
                ],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if (!Auth::guard('web')->check()) {
            return response()->json([
                'success' => true,
                'message' => 'Sesion cerrada',
                'data' => null,
            ]);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Sesion cerrada correctamente',
            'data' => null,
        ]);
    }
}
