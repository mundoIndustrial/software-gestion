<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Services\Asesores\ClientesAsesorService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Asesores\ActualizarClienteAsesorRequest;
use App\Infrastructure\Http\Requests\Asesores\CrearClienteAsesorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ClientesController extends Controller
{
    public function __construct(
        private readonly ClientesAsesorService $clientesAsesorService
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

    public function index()
    {
        $clientes = $this->clientesAsesorService->listarPorUsuario((int) Auth::id(), 15);

        return view('asesores.clientes.index', compact('clientes'));
    }

    public function store(CrearClienteAsesorRequest $request): JsonResponse
    {
        $this->clientesAsesorService->crear((int) Auth::id(), $request->validated());

        return $this->json([
            'success' => true,
            'message' => 'Cliente creado correctamente',
        ]);
    }

    public function update(ActualizarClienteAsesorRequest $request, int|string $id): JsonResponse
    {
        try {
            $this->clientesAsesorService->actualizar((int) Auth::id(), (int) $id, $request->validated());

            return $this->json([
                'success' => true,
                'message' => 'Cliente actualizado',
            ]);
        } catch (AccessDeniedHttpException $e) {
            return $this->failure('No autorizado para actualizar este cliente', 403);
        }
    }

    public function destroy(int|string $id): JsonResponse
    {
        try {
            $this->clientesAsesorService->eliminar((int) Auth::id(), (int) $id);

            return $this->json([
                'success' => true,
                'message' => 'Cliente eliminado',
            ]);
        } catch (AccessDeniedHttpException $e) {
            return $this->failure('No autorizado para eliminar este cliente', 403);
        }
    }
}
