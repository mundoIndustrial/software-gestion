<?php

namespace App\Infrastructure\Http\Controllers\SupervisorPedidos;

use App\Application\SupervisorPedidos\UseCases\UpdateProfileUseCase;
use App\Application\SupervisorPedidos\DTOs\UpdateProfileRequest;
use App\Exceptions\AuthenticationException;
use App\Exceptions\ValidationException;
use App\Exceptions\ApplicationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

/**
 * SupervisorPedidosController
 * 
 * Controlador dedicado a operaciones de perfil del supervisor.
 * Todos los errores se manejan centralizadamente a través del ExceptionHandler.
 * 
 * @package App\Infrastructure\Http\Controllers\SupervisorPedidos
 */
class SupervisorPedidosController extends Controller
{
    private UpdateProfileUseCase $updateProfileUseCase;

    public function __construct(
        UpdateProfileUseCase $updateProfileUseCase
    ) {
        $this->updateProfileUseCase = $updateProfileUseCase;
    }

    /**
     * Mostrar el perfil del supervisor
     */
    public function profile()
    {
        $user = Auth::user();

        if (!$user) {
            throw new AuthenticationException(
                'Debes iniciar sesión para ver tu perfil',
                'profile_view'
            );
        }

        return view('supervisor-pedidos.profile', compact('user'));
    }

    /**
     * Actualizar el perfil del supervisor
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            throw new AuthenticationException(
                'Usuario no autenticado',
                'profile_update'
            );
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'telefono' => 'nullable|string|max:20',
            'ciudad' => 'nullable|string|max:255',
            'departamento' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:500',
            'password' => 'nullable|string|min:8|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048'
        ]);

        $updateRequest = new UpdateProfileRequest(
            userId: (string) $user->id,
            name: $validated['name'],
            email: $validated['email'],
            telefono: $validated['telefono'] ?? null,
            ciudad: $validated['ciudad'] ?? null,
            departamento: $validated['departamento'] ?? null,
            bio: $validated['bio'] ?? null,
            password: $validated['password'] ?? null,
            avatarFile: $request->file('avatar')
        );

        $response = $this->updateProfileUseCase->execute($updateRequest);
        return response()->json($response->toArray());
    }
}
