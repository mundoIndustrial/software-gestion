<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Application\Pedidos\UseCases\EliminarEppUseCase;
use App\Application\Pedidos\UseCases\HomologarEppUseCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use App\Models\PedidoAnexoHistorial;

/**
 * EppsPedidoController
 *
 * Responsabilidad: Gestionar operaciones sobre EPPs de un pedido de producción.
 * - Eliminar EPP de un pedido
 * - Homologar EPP (reemplazar con otro)
 *
 * Patrón: CQRS + Dependency Injection
 * SRP: Solo operaciones de EPPs, sin lógica de negocio
 */
class EppsPedidoController
{
    public function __construct(
        private EliminarEppUseCase $eliminarEppUseCase,
        private HomologarEppUseCase $homologarEppUseCase,
    ) {}

    /**
     * POST /asesores/pedidos/{id}/eliminar-epp
     * Eliminar EPP de un pedido
     */
    public function eliminarEpp(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[EppsPedidoController] POST /asesores/pedidos/{id}/eliminar-epp', [
                'pedido_id' => $id,
            ]);

            $validated = $request->validate([
                'epp_id' => 'required|numeric|min:1',
                'motivo' => 'required|string|min:5|max:1000',
            ]);

            $resultado = $this->eliminarEppUseCase->ejecutar(
                (int) $id,
                (int) $validated['epp_id'],
                $validated['motivo']
            );

            return response()->json($resultado, 200);

        } catch (ModelNotFoundException $e) {
            Log::warning('[EppsPedidoController] EPP o pedido no encontrado', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'EPP o pedido no encontrado',
            ], 404);

        } catch (ValidationException $e) {
            Log::warning('[EppsPedidoController] Validación fallida al eliminar EPP', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[EppsPedidoController] Error eliminando EPP', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /asesores/pedidos/{id}/homologar-epp
     * Homologar EPP: marcar como eliminado y crear uno nuevo con datos editados
     */
    public function homologarEpp(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[EppsPedidoController] POST /asesores/pedidos/{id}/homologar-epp', [
                'pedido_id' => $id,
            ]);

            $validated = $request->validate([
                'pedido_epp_id' => 'required|numeric|min:1',
                'motivo' => 'required|string|min:5|max:1000',
                'cantidad' => 'required|numeric|min:1',
                'observaciones' => 'nullable|string',
                'epp_id' => 'nullable|numeric',
            ]);

            $resultado = $this->homologarEppUseCase->ejecutar(
                (int) $id,
                (int) $validated['pedido_epp_id'],
                $validated['motivo'],
                (int) $validated['cantidad'],
                $validated['observaciones'] ?? null,
                isset($validated['epp_id']) ? (int) $validated['epp_id'] : null
            );

            PedidoAnexoHistorial::registrarEppNuevo(
                (int) $id,
                (int) $resultado['epp_id_nuevo'],
                (int) ($resultado['cambios']['epp_id_nuevo'] ?? 0)
            );

            return response()->json($resultado, 200);

        } catch (ModelNotFoundException $e) {
            Log::warning('[EppsPedidoController] EPP o pedido no encontrado', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'EPP o pedido no encontrado',
            ], 404);

        } catch (ValidationException $e) {
            Log::warning('[EppsPedidoController] Validación fallida al homologar EPP', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[EppsPedidoController] Error homologando EPP', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al homologar EPP: ' . $e->getMessage(),
            ], 500);
        }
    }
}
