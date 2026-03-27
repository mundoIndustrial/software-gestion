<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\DTOs\ActualizarPerfilAsesorDTO;
use App\Application\Pedidos\DTOs\ObtenerPerfilAsesorDTO;
use App\Application\Pedidos\UseCases\ActualizarPerfilAsesorUseCase;
use App\Application\Pedidos\UseCases\ObtenerPerfilAsesorUseCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

final class AsesoresPerfilController extends Controller
{
    public function __construct(
        private readonly ObtenerPerfilAsesorUseCase $obtenerPerfilAsesorUseCase,
        private readonly ActualizarPerfilAsesorUseCase $actualizarPerfilAsesorUseCase
    ) {
    }

    public function profile()
    {
        try {
            $dto = ObtenerPerfilAsesorDTO::crear();
            $user = $this->obtenerPerfilAsesorUseCase->ejecutar($dto);

            return view('asesores.profile', compact('user'));
        } catch (\Throwable $e) {
            abort(500, 'Error al cargar el perfil.');
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
                'telefono' => 'nullable|string|max:20',
                'ciudad' => 'nullable|string|max:255',
                'departamento' => 'nullable|string|max:255',
                'bio' => 'nullable|string|max:500',
                'password' => 'nullable|string|min:8|confirmed',
                'avatar' => 'nullable|image|mimes:jpeg,png,gif,webp|max:2048',
            ]);

            $archivoAvatar = $request->hasFile('avatar') ? $request->file('avatar') : null;
            $dto = ActualizarPerfilAsesorDTO::fromRequest($validated, $archivoAvatar);
            $resultado = $this->actualizarPerfilAsesorUseCase->ejecutar($dto);

            return response()->json($resultado);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Errores de validación', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error al actualizar perfil', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil.',
            ], 500);
        }
    }
}

