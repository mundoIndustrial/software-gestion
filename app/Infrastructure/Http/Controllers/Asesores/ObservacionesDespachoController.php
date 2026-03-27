<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Services\Asesores\ObservacionesDespachoApplicationService;
use App\Events\ObservacionDespachoCreada;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ObservacionesDespachoController extends Controller
{
    public function __construct(
        private readonly ObservacionesDespachoApplicationService $service
    ) {
    }

    public function obtener(Request $request, int|string $id): JsonResponse
    {
        try {
            $pedidoId = $this->service->validarAccesoPedidoPorId(auth()->user(), (int) $id);

            return response()->json([
                'success' => true,
                'data' => $this->service->obtenerObservacionesUnificadas($pedidoId),
            ]);
        } catch (AuthenticationException $e) {
            return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
        } catch (NotFoundHttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    public function resumen(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pedido_ids' => 'required|array',
                'pedido_ids.*' => 'integer',
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->service->obtenerResumen($validated['pedido_ids'], auth()->user()),
            ]);
        } catch (AuthenticationException $e) {
            return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
        }
    }

    public function marcarBodegaVistas(Request $request, int|string $id): JsonResponse
    {
        try {
            $pedidoId = $this->service->validarAccesoPedidoPorId(auth()->user(), (int) $id);
            $this->service->marcarBodegaVistas($pedidoId);

            return response()->json(['success' => true]);
        } catch (AuthenticationException $e) {
            return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
        } catch (NotFoundHttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    public function marcarLeidas(Request $request, int|string $id): JsonResponse
    {
        try {
            $pedidoId = $this->service->validarAccesoPedidoPorId(auth()->user(), (int) $id);
            $this->service->marcarDespachoLeidas($pedidoId);

            return response()->json(['success' => true]);
        } catch (AuthenticationException $e) {
            return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
        } catch (NotFoundHttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    public function guardar(Request $request, int|string $id): JsonResponse
    {
        try {
            $pedidoId = $this->service->validarAccesoPedidoPorId(auth()->user(), (int) $id);

            $validated = $request->validate([
                'contenido' => 'required|string|max:5000',
            ]);

            $row = $this->service->guardar(
                $pedidoId,
                (string) $validated['contenido'],
                auth()->user(),
                $request->ip()
            );

            broadcast(new ObservacionDespachoCreada($row, 'created'))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Observacion guardada exitosamente',
                'data' => $this->service->mapPayload($row),
            ]);
        } catch (AuthenticationException $e) {
            return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
        } catch (NotFoundHttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    public function actualizar(Request $request, int|string $id, string $observacionId): JsonResponse
    {
        try {
            $pedidoId = $this->service->validarAccesoPedidoPorId(auth()->user(), (int) $id);

            $validated = $request->validate([
                'contenido' => 'required|string|max:5000',
            ]);

            $row = $this->service->actualizar(
                $pedidoId,
                $observacionId,
                (string) $validated['contenido'],
                auth()->user()
            );

            broadcast(new ObservacionDespachoCreada($row, 'updated'))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Observacion actualizada correctamente',
                'data' => $this->service->mapPayload($row),
            ]);
        } catch (AuthenticationException $e) {
            return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
        } catch (NotFoundHttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }

    public function eliminar(Request $request, int|string $id, string $observacionId): JsonResponse
    {
        try {
            $pedidoId = $this->service->validarAccesoPedidoPorId(auth()->user(), (int) $id);
            $row = $this->service->eliminar($pedidoId, $observacionId, auth()->user());

            broadcast(new ObservacionDespachoCreada($row, 'deleted'))->toOthers();

            return response()->json([
                'success' => true,
                'message' => 'Observacion eliminada correctamente',
            ]);
        } catch (AuthenticationException $e) {
            return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
        } catch (NotFoundHttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        } catch (AccessDeniedHttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }
    }
}
