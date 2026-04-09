<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Services\Asesores\EntregasDespachoApplicationService;
use App\Infrastructure\Http\Requests\Asesores\ResumenObservacionesDespachoRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntregasDespachoController extends Controller
{
    public function __construct(
        private readonly EntregasDespachoApplicationService $service
    ) {
    }

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function failure(string $message, int $status, array $extra = []): JsonResponse
    {
        return $this->json(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $status);
    }

    private function handleAccessException(\Throwable $e): JsonResponse
    {
        if ($e instanceof AuthenticationException) {
            return $this->failure('No autenticado', 401);
        }

        if ($e instanceof NotFoundHttpException) {
            return $this->failure($e->getMessage(), 404);
        }

        if ($e instanceof AccessDeniedHttpException) {
            return $this->failure($e->getMessage(), 403);
        }

        return $this->failure('Error interno', 500);
    }

    public function obtener(int|string $id): JsonResponse
    {
        try {
            $pedidoId = $this->service->validarAccesoPedidoPorId(auth()->user(), (int) $id);

            return $this->json([
                'success' => true,
                'data' => $this->service->obtenerPendientesPorPedido($pedidoId),
            ]);
        } catch (\Throwable $e) {
            return $this->handleAccessException($e);
        }
    }

    public function resumen(ResumenObservacionesDespachoRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            return $this->json([
                'success' => true,
                'data' => $this->service->obtenerResumen($validated['pedido_ids'], auth()->user()),
            ]);
        } catch (\Throwable $e) {
            return $this->handleAccessException($e);
        }
    }

    public function marcar(int|string $id, int|string $detalleId): JsonResponse
    {
        try {
            $pedidoId = $this->service->validarAccesoPedidoPorId(auth()->user(), (int) $id);
            $result = $this->service->marcarEnDespacho($pedidoId, (int) $detalleId);

            return $this->json([
                'success' => true,
                'message' => $result['already_marked']
                    ? 'El ítem ya estaba marcado en despacho'
                    : 'Ítem marcado en despacho correctamente',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return $this->handleAccessException($e);
        }
    }
}

