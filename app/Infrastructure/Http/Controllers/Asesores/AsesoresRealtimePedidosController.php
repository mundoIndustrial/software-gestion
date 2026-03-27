<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

final class AsesoresRealtimePedidosController extends Controller
{
    public function __construct(
        private readonly ListarProduccionPedidosUseCase $listarProduccionPedidosUseCase
    ) {
    }

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function failure(string $message, int $status): JsonResponse
    {
        return $this->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    public function listar(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return $this->failure('Unauthorized', 403);
        }

        $hasPermission = $user->hasRole('asesor')
            || $user->hasRole('admin')
            || $user->hasRole('supervisor_pedidos')
            || $user->hasRole('despacho')
            || $user->hasRole('insumos');

        if (!$hasPermission) {
            return $this->failure('Unauthorized', 403);
        }

        $dto = ListarProduccionPedidosDTO::fromRequest(
            tipo: null,
            filtros: ['per_page' => 500],
            usuarioId: $user->id,
            soloAsesor: (bool) $user->hasRole('asesor')
        );

        $pedidos = $this->listarProduccionPedidosUseCase->ejecutar($dto);

        return $this->json([
            'success' => true,
            'data' => $pedidos->getCollection()->values()->all(),
        ]);
    }
}
