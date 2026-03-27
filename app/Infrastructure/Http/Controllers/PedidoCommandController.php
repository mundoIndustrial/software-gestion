<?php

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\UseCases\AnularProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CalcularFechaEntregaEstimadaUseCase;
use App\Application\Pedidos\UseCases\ActualizarPedidoCamposUseCase;
use App\Application\Pedidos\UseCases\CambiarEstadoPedidoUseCase;
use App\Application\Asesores\UseCases\ResolverPedidoIdAsesorUseCase;
use App\Application\Pedidos\DTOs\CrearPedidoDTO;
use App\Application\Pedidos\DTOs\ConfirmarPedidoInputDTO;
use App\Application\Pedidos\DTOs\AnularProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\ActualizarPedidoCamposDTO;
use App\Application\Pedidos\DTOs\CambiarEstadoPedidoDTO;
use App\Domain\Pedidos\Exceptions\PedidoNoEncontrado;
use App\Domain\Pedidos\Exceptions\EstadoPedidoInvalido;

/**
 * PedidoCommandController
 *
 * Lado escritura (CQRS write side) para pedidos.
 * Maneja creación, confirmación, cancelación y actualización de estado/descripción.
 */
class PedidoCommandController extends Controller
{
    public function __construct(
        private CrearPedidoUseCase $crearPedidoUseCase,
        private ConfirmarPedidoUseCase $confirmarPedidoUseCase,
        private AnularProduccionPedidoUseCase $anularProduccionPedidoUseCase,
        private CalcularFechaEntregaEstimadaUseCase $calcularFechaEntregaEstimadaUseCase,
        private ActualizarPedidoCamposUseCase $actualizarPedidoCamposUseCase,
        private CambiarEstadoPedidoUseCase $cambiarEstadoPedidoUseCase,
        private ResolverPedidoIdAsesorUseCase $resolverPedidoIdAsesorUseCase
    ) {}

    /**
     * POST /api/pedidos
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'cliente_id' => 'required|integer',
                'descripcion' => 'required|string|max:1000',
                'observaciones' => 'nullable|string|max:1000',
                'prendas' => 'required|array|min:1',
                'prendas.*.prenda_id' => 'required|integer',
                'prendas.*.descripcion' => 'required|string',
                'prendas.*.cantidad' => 'required|integer|min:1',
                'prendas.*.tallas' => 'required|array',
            ]);

            $dto = CrearPedidoDTO::fromRequest($request->all());
            $response = $this->crearPedidoUseCase->ejecutar($dto);

            return response()->json([
                'success' => true,
                'message' => $response->mensaje,
                'data' => $response->toArray()
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/pedidos/{id}/confirmar
     */
    public function confirmar(int $id): JsonResponse
    {
        try {
            $dto = ConfirmarPedidoInputDTO::fromId($id);
            $response = $this->confirmarPedidoUseCase->ejecutar($dto);

            return response()->json([
                'success' => true,
                'message' => 'Pedido confirmado exitosamente',
                'data' => $response->toArray()
            ], 200);

        } catch (PedidoNoEncontrado $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);

        } catch (EstadoPedidoInvalido $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede confirmar el pedido: ' . $e->getMessage(),
            ], 422);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/pedidos/{id}/cancelar
     */
    public function cancelar(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'novedad' => 'required|string|min:10|max:500',
            ], [
                'novedad.required' => 'La novedad es obligatoria',
                'novedad.min' => 'La novedad debe tener al menos 10 caracteres',
                'novedad.max' => 'La novedad no puede exceder 500 caracteres',
            ]);

            $usuario = auth()->user();
            $usuarioId = (int) ($usuario?->id ?? 0);
            $pedidoId = $this->resolverPedidoIdAsesorUseCase->ejecutar((string) $id, $usuarioId);
            $nombreUsuario = $usuario?->name ?: 'Sistema';

            $rolUsuario = 'Sin rol';
            if ($usuario && method_exists($usuario, 'roles')) {
                $rolUsuario = $usuario->roles()->first()?->name ?? 'Sin rol';
            }

            $dto = AnularProduccionPedidoDTO::fromRequest((string) $pedidoId, [
                'razon' => (string) $request->input('novedad'),
                'nombreUsuario' => $nombreUsuario,
                'rolUsuario' => $rolUsuario,
            ]);
            $response = $this->anularProduccionPedidoUseCase->ejecutarConDTO($dto);

            return response()->json([
                'success' => true,
                'message' => 'Pedido anulado correctamente',
                'data' => $response->toArray()
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al anular pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/pedidos/{id}/actualizar-descripcion
     */
    public function actualizarDescripcion(Request $request, int $id): JsonResponse
    {
        try {
            \Log::info('[actualizarDescripcion] Iniciando', [
                'pedido_id' => $id,
                'metodo' => $request->method(),
                'ruta' => $request->path(),
            ]);

            $request->validate([
                'descripcion' => 'nullable|string|max:2000',
                'cliente' => 'nullable|string|max:500',
                'forma_de_pago' => 'nullable|string|max:500',
                'novedades' => 'nullable|string|max:2000',
                'justificacion' => 'nullable|string|max:1000'
            ]);

            $usuario = auth()->user();
            $nombreUsuario = $usuario?->name ?: 'Sistema';
            $rolUsuario = 'Sin rol';
            if ($usuario && method_exists($usuario, 'roles')) {
                $rolUsuario = $usuario->roles()->first()?->name ?? 'Sin rol';
            }

            $dto = ActualizarPedidoCamposDTO::fromRequest(
                pedidoId: $id,
                data: $request->all(),
                usuarioId: auth()->id(),
                nombreUsuario: $nombreUsuario,
                rolUsuario: $rolUsuario
            );
            $pedidoActualizado = $this->actualizarPedidoCamposUseCase->ejecutar($dto);

            return response()->json([
                'success' => true,
                'message' => 'Cambios guardados exitosamente',
                'data' => $pedidoActualizado
            ], 200);

        } catch (\RuntimeException $e) {
            if ($e->getCode() !== 404) {
                throw $e;
            }

            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('[actualizarDescripcion] Error:', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar cambios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/pedidos/{id}/actualizar-estado
     */
    public function actualizarEstado(Request $request, int $id): JsonResponse
    {
        try {
            \Log::info('[actualizarEstado] Iniciando', [
                'pedido_id' => $id,
                'nuevo_estado' => $request->input('estado'),
                'metodo' => $request->method(),
                'ruta' => $request->path(),
                'usuario_id' => auth()->id()
            ]);

            $request->validate([
                'estado' => 'required|string|in:Pendiente,No iniciado,En Ejecución,Entregado,Anulada,PENDIENTE_SUPERVISOR,PENDIENTE_INSUMOS,pendiente_cartera,RECHAZADO_CARTERA,DEVUELTO_A_ASESORA'
            ]);

            $pedido = \App\Models\PedidoProduccion::find($id);
            if (!$pedido) {
                \Log::warning('[actualizarEstado] Pedido no encontrado', ['pedido_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            $nuevoEstado = (string) $request->input('estado');
            $estadoAnterior = (string) ($pedido->estado ?? '');

            if ($estadoAnterior === $nuevoEstado) {
                \Log::info('[actualizarEstado] El estado es el mismo, no se actualiza', [
                    'pedido_id' => $id,
                    'estado' => $nuevoEstado
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'El estado ya es el mismo',
                    'data' => [
                        'id' => $pedido->id,
                        'estado' => $nuevoEstado,
                        'estado_anterior' => $estadoAnterior
                    ]
                ]);
            }

            $dto = CambiarEstadoPedidoDTO::fromRequest($id, [
                'estado' => $nuevoEstado,
                'nombre_usuario' => auth()->user()?->name ?? 'Sistema',
                'registrar_novedad' => true,
            ]);
            $pedidoActualizado = $this->cambiarEstadoPedidoUseCase->ejecutar($dto);

            \Log::info('[actualizarEstado] Novedad registrada', [
                'pedido_id' => $id,
                'novedad' => "Estado cambiado de '{$estadoAnterior}' a '{$nuevoEstado}' por " . (auth()->user()?->name ?? 'Sistema')
            ]);

            \Log::info('[actualizarEstado] Estado actualizado exitosamente', [
                'pedido_id' => $id,
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => $nuevoEstado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'data' => [
                    'id' => $pedidoActualizado->id ?? $id,
                    'estado' => $nuevoEstado,
                    'estado_anterior' => $estadoAnterior,
                    'novedades' => $pedidoActualizado->novedades ?? null
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('[actualizarEstado] Error:', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/pedidos/{id}/calcular-fecha-entrega
     * 
     * REFACTORIZADO: Calcula la fecha estimada de entrega usando días hábiles
     * Las excepciones se manejan centralmente en ExceptionHandler
     */
    public function calcularFechaEntrega(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'dias_estimados' => 'required|integer|min:1|max:365'
        ]);

        $response = $this->calcularFechaEntregaEstimadaUseCase->ejecutar(
            $id,
            $request->input('dias_estimados')
        );

        return response()->json($response, 200);
    }
}
