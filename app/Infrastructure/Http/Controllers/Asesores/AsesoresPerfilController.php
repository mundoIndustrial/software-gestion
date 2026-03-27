<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\DTOs\ActualizarPerfilAsesorDTO;
use App\Application\Pedidos\DTOs\ObtenerPerfilAsesorDTO;
use App\Application\Pedidos\UseCases\ActualizarPerfilAsesorUseCase;
use App\Application\Pedidos\UseCases\ObtenerPerfilAsesorUseCase;
use App\Infrastructure\Http\Requests\Asesores\ActualizarPerfilAsesorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

final class AsesoresPerfilController extends Controller
{
    public function __construct(
        private readonly ObtenerPerfilAsesorUseCase $obtenerPerfilAsesorUseCase,
        private readonly ActualizarPerfilAsesorUseCase $actualizarPerfilAsesorUseCase
    ) {
    }

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function failure(string $message, int $status = 500, array $extra = []): JsonResponse
    {
        return $this->json(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $status);
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

    public function updateProfile(ActualizarPerfilAsesorRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $archivoAvatar = $request->hasFile('avatar') ? $request->file('avatar') : null;
            $dto = ActualizarPerfilAsesorDTO::fromRequest($validated, $archivoAvatar);
            $resultado = $this->actualizarPerfilAsesorUseCase->ejecutar($dto);

            return $this->json($resultado);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Errores de validacion', ['errors' => $e->errors()]);
            return $this->failure('Error de validacion', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al actualizar perfil', ['error' => $e->getMessage()]);
            return $this->failure('Error al actualizar el perfil.', 500);
        }
    }
}
