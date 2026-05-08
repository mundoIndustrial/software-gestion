<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Application\Pedidos\UseCases\EliminarEppUseCase;
use App\Application\Pedidos\UseCases\HomologarEppUseCase;
use App\Models\PedidoProduccion;
use App\Application\Services\Asesores\PrendaPedidoEdicionAuditoriaService;
use App\Infrastructure\Http\Requests\Asesores\EliminarEppRequest;
use App\Infrastructure\Http\Requests\Asesores\HomologarEppRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * EppsPedidoController
 *
 * Responsabilidad: Gestionar operaciones sobre EPPs de un pedido de produccion.
 * - Eliminar EPP de un pedido
 * - Homologar EPP (reemplazar con otro)
 *
 * Patron: CQRS + Dependency Injection
 * SRP: Solo operaciones de EPPs, sin logica de negocio
 */
class EppsPedidoController
{
    public function __construct(
        private EliminarEppUseCase $eliminarEppUseCase,
        private HomologarEppUseCase $homologarEppUseCase,
        private PrendaPedidoEdicionAuditoriaService $prendaPedidoEdicionAuditoriaService,
    ) {}

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

    /**
     * POST /asesores/pedidos/{id}/eliminar-epp
     * Eliminar EPP de un pedido
     */
    public function eliminarEpp(EliminarEppRequest $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[EppsPedidoController] POST /asesores/pedidos/{id}/eliminar-epp', [
                'pedido_id' => $id,
            ]);

            $validated = $request->validated();

            $resultado = $this->eliminarEppUseCase->ejecutar(
                (int) $id,
                (int) $validated['epp_id'],
                $validated['motivo']
            );

            return $this->json($resultado, 200);
        } catch (ModelNotFoundException $e) {
            Log::warning('[EppsPedidoController] EPP o pedido no encontrado', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('EPP o pedido no encontrado', 404);
        } catch (ValidationException $e) {
            Log::warning('[EppsPedidoController] Validacion fallida al eliminar EPP', [
                'errors' => $e->errors(),
            ]);

            return $this->failure('Validacion fallida', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('[EppsPedidoController] Error eliminando EPP', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->failure('Error al eliminar EPP: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /asesores/pedidos/{id}/homologar-epp
     * Homologar EPP: marcar como eliminado y crear uno nuevo con datos editados
     */
    public function homologarEpp(HomologarEppRequest $request, int|string $id): JsonResponse
    {
        try {
            $pedido = PedidoProduccion::query()
                ->select(['id', 'estado'])
                ->findOrFail((int) $id);

            if (trim((string) $pedido->estado) === 'Entregado') {
                return $this->failure('No se puede homologar EPP en pedidos Entregados', 422);
            }

            Log::info('[EppsPedidoController] POST /asesores/pedidos/{id}/homologar-epp', [
                'pedido_id' => $id,
            ]);

            $validated = $request->validated();

            $resultado = $this->homologarEppUseCase->ejecutar(
                (int) $id,
                (int) $validated['pedido_epp_id'],
                $validated['motivo'],
                (int) $validated['cantidad'],
                $validated['observaciones'] ?? null,
                isset($validated['epp_id']) ? (int) $validated['epp_id'] : null,
                auth()?->user()?->name,
                now(),
                implode(', ', auth()?->user()?->getRoleNames()?->toArray() ?? ['Asesor'])
            );

            $this->prendaPedidoEdicionAuditoriaService->registrarEppNuevo(
                (int) $id,
                (int) $resultado['epp_id_nuevo'],
                (int) ($resultado['cambios']['epp_id_nuevo'] ?? 0)
            );

            return $this->json($resultado, 200);
        } catch (ModelNotFoundException $e) {
            Log::warning('[EppsPedidoController] EPP o pedido no encontrado', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('EPP o pedido no encontrado', 404);
        } catch (ValidationException $e) {
            Log::warning('[EppsPedidoController] Validacion fallida al homologar EPP', [
                'errors' => $e->errors(),
            ]);

            return $this->failure('Validacion fallida', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('[EppsPedidoController] Error homologando EPP', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->failure('Error al homologar EPP: ' . $e->getMessage(), 500);
        }
    }
}
