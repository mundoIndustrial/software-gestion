<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Application\Services\Asesores\PrendasPedidoApplicationFacadeService;
use App\Infrastructure\Http\Requests\Asesores\ActualizarPrendaRequest;
use App\Infrastructure\Http\Requests\Asesores\AgregarPrendaCompletaRequest;
use App\Infrastructure\Http\Requests\Asesores\AgregarPrendaSimpleRequest;
use App\Infrastructure\Http\Requests\Asesores\ActualizarPrendaCompletaRequest;
use App\Infrastructure\Http\Requests\Asesores\EliminarPrendaRequest;
use App\Infrastructure\Http\Requests\Asesores\RenderItemCardRequest;
use App\Infrastructure\Http\Resources\PrendaPedidoResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * PrendasPedidoController
 *
 * Responsabilidad: Gestionar prendas asociadas a pedidos de produccion.
 * - Agregar, actualizar y eliminar prendas (simple y completa con imagenes)
 * - Renderizar item-card para interfaz dinamica
 * - Obtener datos completos de prenda para edicion
 * - Eliminar imagenes de prenda
 *
 * Patron: CQRS + Dependency Injection
 * SRP: Solo operaciones de prendas e imagenes, sin logica de negocio
 */
class PrendasPedidoController
{
    private const ERROR_ACTUALIZAR_PRENDA_PREFIX = 'Error al actualizar prenda: ';
    private const VALIDACION_FALLIDA = 'Validacion fallida';

    public function __construct(
        private PrendasPedidoApplicationFacadeService $prendasPedidoApplicationFacadeService,
    ) {}

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function jsonError(string $error, string $message, int $status): JsonResponse
    {
        return $this->json([
            'error' => $error,
            'message' => $message,
        ], $status);
    }

    private function jsonFailure(string $message, int $status, array $extra = []): JsonResponse
    {
        return $this->json(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $status);
    }

    /**
     * POST /api/pedidos/{id}/prendas
     * Agregar prenda simple a pedido
     */
    public function agregarPrenda(AgregarPrendaSimpleRequest $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PrendasPedidoController] POST /api/pedidos/{id}/prendas', ['id' => $id]);

            $validated = $request->validated();

            $pedido = $this->prendasPedidoApplicationFacadeService->agregarPrenda($id, $validated);

            Log::info('[PrendasPedidoController] Prenda agregada exitosamente', [
                'pedido_id' => $pedido->id,
            ]);

            return $this->json($pedido, 201);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PrendasPedidoController] Validacion de prenda fallida', [
                'error' => $e->getMessage(),
            ]);

            return $this->jsonError('Validacion de prenda fallida', $e->getMessage(), 422);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error agregando prenda', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->jsonError('Error agregando prenda', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/pedidos/{id}/prendas
     * Obtener todas las prendas de un pedido
     */
    public function obtenerPrendas(int|string $id): JsonResponse
    {
        try {
            Log::info('[PrendasPedidoController] GET /api/pedidos/{id}/prendas', ['id' => $id]);

            $prendas = $this->prendasPedidoApplicationFacadeService->obtenerPrendas($id);

            Log::info('[PrendasPedidoController] Prendas obtenidas exitosamente', [
                'pedido_id' => $id,
                'total_prendas' => $prendas->count(),
            ]);

            return $this->json($prendas);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error obteniendo prendas', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->jsonError('Error obteniendo prendas', $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/pedidos/render-item-card
     * Renderizar componente item-card para agregar dinamicamente
     */
    public function renderItemCard(RenderItemCardRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $html = $this->prendasPedidoApplicationFacadeService->renderItemCard($validated);

            return $this->json([
                'success' => true,
                'html' => $html,
            ]);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error renderizando item-card', [
                'error' => $e->getMessage(),
            ]);

            return $this->jsonFailure('Error renderizando componente', 500, [
                'error' => 'Error renderizando componente',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * POST (sin ruta registrada actualmente)
     * Actualizar datos basicos de una prenda especifica dentro de un pedido
     */
    public function actualizarPrenda(ActualizarPrendaRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $prenda = $this->prendasPedidoApplicationFacadeService->actualizarPrenda($validated['pedidoId'], $validated);

            Log::info('[PrendasPedidoController] Prenda actualizada exitosamente', [
                'pedido_id' => $validated['pedidoId'],
                'prenda_index' => $validated['prendasIndex'],
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Prenda actualizada correctamente',
                'prenda' => $prenda,
            ]);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PrendasPedidoController] Validacion de prenda fallida', [
                'error' => $e->getMessage(),
            ]);

            return $this->jsonFailure($e->getMessage(), 404);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error actualizando prenda', [
                'error' => $e->getMessage(),
            ]);

            return $this->jsonFailure(self::ERROR_ACTUALIZAR_PRENDA_PREFIX . $e->getMessage(), 500);
        }
    }

    /**
     * POST /asesores/pedidos/{id}/agregar-prenda
     * Agregar prenda completa (con telas e imagenes) al pedido en edicion
     */
    public function agregarPrendaCompleta(AgregarPrendaCompletaRequest $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PrendasPedidoController] POST /asesores/pedidos/{id}/agregar-prenda', ['id' => $id]);
            $validated = $request->validated();

            $prenda = $this->prendasPedidoApplicationFacadeService->agregarPrendaCompleta(
                $request,
                (int) $id,
                $validated
            );

            Log::info('[PrendasPedidoController] Prenda completa agregada exitosamente', [
                'pedido_id' => $id,
                'prenda_id' => $prenda->id,
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Prenda agregada correctamente a la base de datos',
                'prenda' => $prenda->toArray(),
            ], 201);

        } catch (ValidationException $e) {
            Log::warning('[PrendasPedidoController] Validacion fallida', [
                'errors' => $e->errors(),
            ]);

            return $this->jsonFailure(self::VALIDACION_FALLIDA, 422, [
                'errors' => $e->errors(),
            ]);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error agregando prenda completa', [
                'error' => $e->getMessage(),
            ]);

            return $this->jsonFailure('Error al agregar prenda: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /asesores/pedidos/{id}/actualizar-prenda
     * Actualizar una prenda existente en un pedido (con telas e imagenes)
     */
    public function actualizarPrendaCompleta(ActualizarPrendaCompletaRequest $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PrendasPedidoController] POST /asesores/pedidos/{id}/actualizar-prenda', ['id' => $id]);

            Log::info('[PrendasPedidoController] Request raw data', [
                'origen' => $request->input('origen'),
                'de_bodega' => $request->input('de_bodega'),
                'all_inputs' => $request->all()
            ]);

            $validated = $request->validated();

            $prenda = $this->prendasPedidoApplicationFacadeService->actualizarPrendaCompleta(
                $request,
                (int) $id,
                $validated
            );

            Log::info('[PrendasPedidoController] Prenda completa actualizada exitosamente', [
                'pedido_id' => $id,
                'prenda_id' => $prenda->id,
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Prenda actualizada correctamente en la base de datos',
                'prenda'  => PrendaPedidoResource::make($prenda)->resolve(),
            ]);

        } catch (ValidationException $e) {
            Log::warning('[PrendasPedidoController] Validacion fallida en actualizacion', [
                'errors' => $e->errors(),
            ]);

            return $this->jsonFailure(self::VALIDACION_FALLIDA, 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\DomainException $e) {
            Log::warning('[PrendasPedidoController] Edicion bloqueada por consecutivo', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->jsonFailure($e->getMessage(), 409, [
                'bloqueada_edicion' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error actualizando prenda completa', [
                'error' => $e->getMessage(),
            ]);

            return $this->jsonFailure(self::ERROR_ACTUALIZAR_PRENDA_PREFIX . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/asesores/pedidos-produccion/{pedidoId}/prendas/{prendaId}
     * Endpoint puente para mantener compatibilidad del modal simple legacy.
     */
    public function actualizarPrendaDesdeProduccion(Request $request, int|string $pedidoId, int|string $prendaId): JsonResponse
    {
        $response = null;

        try {
            $nombrePrenda = $request->input('nombre_prenda')
                ?? $request->input('nombre')
                ?? $request->input('nombre_producto');

            if (empty($nombrePrenda)) {
                $datosPrenda = $this->prendasPedidoApplicationFacadeService->obtenerDatosPrendaEdicion((int) $pedidoId, (int) $prendaId);
                $nombrePrenda = $datosPrenda['nombre_prenda']
                    ?? $datosPrenda['nombre']
                    ?? $datosPrenda['nombre_producto']
                    ?? 'PRENDA';
            }

            $payload = [
                'prenda_id' => (int) $prendaId,
                'nombre_prenda' => $nombrePrenda,
                'novedad' => $request->input('novedad', 'Actualizacion desde modal simple'),
            ];

            $optionalKeys = [
                'descripcion',
                'origen',
                'de_bodega',
                'tallas',
                'variantes',
                'colores_telas',
                'fotos_telas',
                'fotosTelas',
                'procesos',
                'fotos_procesos',
                'asignaciones_colores',
                'imagenes_existentes',
                'imagenes_a_eliminar',
                'procesos_a_eliminar',
            ];

            foreach ($optionalKeys as $key) {
                if ($request->has($key)) {
                    $payload[$key] = $request->input($key);
                }
            }

            $prenda = $this->prendasPedidoApplicationFacadeService->actualizarPrendaCompleta(
                $request,
                (int) $pedidoId,
                $payload
            );

            $response = $this->json([
                'success' => true,
                'message' => 'Prenda actualizada correctamente',
                'prenda' => (new PrendaPedidoResource($prenda))->resolve(),
            ]);
        } catch (ModelNotFoundException $e) {
            $response = $this->jsonFailure('Prenda no encontrada para actualizar', 404);
        } catch (ValidationException $e) {
            $response = $this->jsonFailure(self::VALIDACION_FALLIDA, 422, ['errors' => $e->errors()]);
        } catch (\DomainException $e) {
            $response = $this->jsonFailure($e->getMessage(), 409, [
                'bloqueada_edicion' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('[PrendasPedidoController] Error en endpoint puente actualizarPrendaDesdeProduccion', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);

            $response = $this->jsonFailure(self::ERROR_ACTUALIZAR_PRENDA_PREFIX . $e->getMessage(), 500);
        }

        return $response;
    }

    /**
     * DELETE /pedidos/{pedidoId}/imagen/{tipo}/{id}
     * Eliminar imagen de prenda, tela o proceso
     */
    public function eliminarImagen(int $pedidoId, string $tipo, int $id): JsonResponse
    {
        $response = null;

        try {
            Log::info('[PrendasPedidoController] DELETE imagen', [
                'pedido_id' => $pedidoId,
                'tipo'      => $tipo,
                'id'        => $id,
            ]);

            $resultado = $this->prendasPedidoApplicationFacadeService->eliminarImagen($pedidoId, $tipo, $id);

            $response = $this->json($resultado);

        } catch (\InvalidArgumentException $e) {
            $response = $this->jsonFailure($e->getMessage(), 400);

        } catch (ModelNotFoundException $e) {
            Log::warning('[PrendasPedidoController] Imagen no encontrada', [
                'tipo' => $tipo,
                'id'   => $id,
            ]);

            $response = $this->jsonFailure('Imagen no encontrada', 404);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error eliminando imagen', [
                'tipo'  => $tipo,
                'id'    => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response = $this->jsonFailure('Error al eliminar imagen: ' . $e->getMessage(), 500);
        }

        return $response;
    }

    /**
     * POST /asesores/pedidos/{id}/eliminar-prenda
     * Eliminar una prenda de un pedido y registrar el motivo en novedades
     */
    public function eliminarPrenda(EliminarPrendaRequest $request, int|string $id): JsonResponse
    {
        $response = null;

        try {
            Log::info('[PrendasPedidoController] POST /asesores/pedidos/{id}/eliminar-prenda', [
                'pedido_id' => $id,
            ]);
            $validated = $request->validated();

            $resultado = $this->prendasPedidoApplicationFacadeService->eliminarPrenda(
                (int) $id,
                (int) $validated['prenda_id'],
                $validated['motivo']
            );

            $response = $this->json($resultado);

        } catch (ModelNotFoundException $e) {
            Log::warning('[PrendasPedidoController] Prenda o pedido no encontrado', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $response = $this->jsonFailure('Prenda o pedido no encontrado', 404);

        } catch (ValidationException $e) {
            Log::warning('[PrendasPedidoController] Validacion fallida al eliminar prenda', [
                'errors' => $e->errors(),
            ]);

            $response = $this->jsonFailure(self::VALIDACION_FALLIDA, 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\DomainException $e) {
            Log::warning('[PrendasPedidoController] Eliminacion bloqueada por consecutivo', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $response = $this->jsonFailure($e->getMessage(), 409, [
                'bloqueada_eliminacion' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error eliminando prenda', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response = $this->jsonFailure('Error al eliminar prenda: ' . $e->getMessage(), 500);
        }

        return $response;
    }

    /**
     * GET /asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos
     * Obtener datos completos de una prenda con procesos para edicion en modal
     */
    public function obtenerDatosPrendaEdicion(int|string $pedidoId, int|string $prendaId): JsonResponse
    {
        try {
            Log::info(' [PRENDA-DATOS-INICIO] Endpoint llamado', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'timestamp' => now()
            ]);

            $resultado = $this->prendasPedidoApplicationFacadeService->obtenerDatosPrendaEdicion(
                (int) $pedidoId,
                (int) $prendaId
            );

            return $this->json([
                'success' => true,
                'prenda' => $resultado['prenda'],
                'pedido' => $resultado['pedido'],
            ]);

        } catch (ModelNotFoundException $e) {
            Log::warning(' [PRENDA-DATOS] Prenda no encontrada', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId
            ]);

            return $this->jsonFailure('Prenda no encontrada', 404);
        } catch (\DomainException $e) {
            Log::warning(' [PRENDA-DATOS] Edicion bloqueada por consecutivo', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);

            return $this->jsonFailure($e->getMessage(), 409, [
                'bloqueada_edicion' => true,
            ]);

        } catch (\Exception $e) {
            Log::error(' [PRENDA-DATOS] Error obteniendo datos de prenda', [
                'error' => $e->getMessage(),
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonFailure('Error al obtener datos de prenda: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /asesores/pedidos-produccion/{pedidoId}/datos-edicion
     * Obtener datos del pedido para edicion general (sin prenda especifica)
     */
    public function obtenerDatosEdicion(int $pedidoId): JsonResponse
    {
        try {
            Log::info('[PrendasPedidoController] GET /pedidos-produccion/{pedidoId}/datos-edicion', [
                'pedido_id' => $pedidoId,
            ]);

            $resultado = $this->prendasPedidoApplicationFacadeService->obtenerDatosEdicion($pedidoId);
            $pedido = $resultado['data'];

            if (!$pedido) {
                Log::warning('[PrendasPedidoController] Pedido no encontrado para edicion', [
                    'pedido_id' => $pedidoId,
                ]);

                return $this->jsonFailure('Pedido no encontrado', 404);
            }

            Log::info('[PrendasPedidoController] Datos de edicion obtenidos', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $resultado['numero_pedido'],
                'prendas_count' => $resultado['prendas_count'],
            ]);

            return $this->json([
                'success' => true,
                'pedido_id' => $resultado['pedido_id'],
                'numero_pedido' => $resultado['numero_pedido'],
                'cliente' => $resultado['cliente'],
                'prendas_count' => $resultado['prendas_count'],
                'data' => $resultado['data'],
            ]);

        } catch (\Exception $e) {
            Log::error('[PrendasPedidoController] Error obteniendo datos de edicion', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);

            return $this->jsonFailure('Error al obtener datos de edicion: ' . $e->getMessage(), 500);
        }
    }
}
