<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Services\Asesores\ObservacionesDespachoApplicationService;
use App\Events\ObservacionDespachoCreada;
use App\Infrastructure\Http\Requests\Asesores\GuardarObservacionDespachoRequest;
use App\Infrastructure\Http\Requests\Asesores\ResumenObservacionesDespachoRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ObservacionesDespachoController extends Controller
{
    public function __construct(
        private readonly ObservacionesDespachoApplicationService $service
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
                'data' => $this->service->obtenerObservacionesUnificadas($pedidoId),
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

    public function marcarBodegaVistas(int|string $id): JsonResponse
    {
        try {
            $pedidoId = $this->service->validarAccesoPedidoPorId(auth()->user(), (int) $id);
            $this->service->marcarBodegaVistas($pedidoId);

            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->handleAccessException($e);
        }
    }

    public function marcarLeidas(int|string $id): JsonResponse
    {
        try {
            $pedidoId = $this->service->validarAccesoPedidoPorId(auth()->user(), (int) $id);
            $this->service->marcarDespachoLeidas($pedidoId);

            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->handleAccessException($e);
        }
    }

    public function guardar(GuardarObservacionDespachoRequest $request, int|string $id): JsonResponse
    {
        try {
            $pedidoId = $this->service->validarAccesoPedidoPorId(auth()->user(), (int) $id);
            $validated = $request->validated();

            $row = $this->service->guardar(
                $pedidoId,
                (string) $validated['contenido'],
                auth()->user(),
                $request->ip()
            );

            broadcast(new ObservacionDespachoCreada($row, 'created'))->toOthers();

            return $this->json([
                'success' => true,
                'message' => 'Observacion guardada exitosamente',
                'data' => $this->service->mapPayload($row),
            ]);
        } catch (\Throwable $e) {
            return $this->handleAccessException($e);
        }
    }

    public function actualizar(GuardarObservacionDespachoRequest $request, int|string $id, string $observacionId): JsonResponse
    {
        try {
            $pedidoId = $this->service->validarAccesoPedidoPorId(auth()->user(), (int) $id);
            $validated = $request->validated();

            $row = $this->service->actualizar(
                $pedidoId,
                $observacionId,
                (string) $validated['contenido'],
                auth()->user()
            );

            broadcast(new ObservacionDespachoCreada($row, 'updated'))->toOthers();

            return $this->json([
                'success' => true,
                'message' => 'Observacion actualizada correctamente',
                'data' => $this->service->mapPayload($row),
            ]);
        } catch (\Throwable $e) {
            return $this->handleAccessException($e);
        }
    }

    public function eliminar(int|string $id, string $observacionId): JsonResponse
    {
        try {
            $pedidoId = $this->service->validarAccesoPedidoPorId(auth()->user(), (int) $id);
            $row = $this->service->eliminar($pedidoId, $observacionId, auth()->user());

            broadcast(new ObservacionDespachoCreada($row, 'deleted'))->toOthers();

            return $this->json([
                'success' => true,
                'message' => 'Observacion eliminada correctamente',
            ]);
        } catch (\Throwable $e) {
            return $this->handleAccessException($e);
        }
    }
}
