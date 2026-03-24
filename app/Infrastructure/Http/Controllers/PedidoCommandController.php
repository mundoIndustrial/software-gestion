<?php

namespace App\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\UseCases\CancelarPedidoUseCase;
use App\Application\Pedidos\UseCases\CalcularFechaEntregaEstimadaUseCase;
use App\Application\Pedidos\DTOs\CrearPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
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
        private CancelarPedidoUseCase $cancelarPedidoUseCase,
        private CalcularFechaEntregaEstimadaUseCase $calcularFechaEntregaEstimadaUseCase,
        private PedidoRepository $pedidoRepository
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
            $response = $this->confirmarPedidoUseCase->ejecutar($id);

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
    public function cancelar(int $id): JsonResponse
    {
        try {
            $response = $this->cancelarPedidoUseCase->ejecutar($id);

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado exitosamente',
                'data' => $response->toArray()
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar pedido: ' . $e->getMessage()
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

            $pedido = \App\Models\PedidoProduccion::findOrFail($id);

            if ($request->has('cliente') && !is_null($request->input('cliente'))) {
                $pedido->cliente = $request->input('cliente');
            }

            if ($request->has('forma_de_pago') && !is_null($request->input('forma_de_pago'))) {
                $pedido->forma_de_pago = $request->input('forma_de_pago');
            }

            if ($request->has('novedades') && !is_null($request->input('novedades'))) {
                $pedido->novedades = $request->input('novedades');
            }

            if ($request->has('justificacion') && !is_null($request->input('justificacion')) && !empty($request->input('justificacion'))) {
                $justificacion = $request->input('justificacion');
                $novedadesActuales = $pedido->novedades ?: '';

                $usuario = auth()->user();

                \Log::info('[actualizarDescripcion] Usuario autenticado:', [
                    'usuario' => $usuario ? $usuario->toArray() : null,
                    'auth_check' => auth()->check(),
                    'usuario_id' => auth()->id(),
                ]);

                $nombreUsuario = 'Sistema';
                $rolUsuario = 'Sin rol';

                if ($usuario) {
                    $nombreUsuario = $usuario->name ?: 'Usuario';
                    $rolesUsuario = $usuario->roles();

                    \Log::info('[actualizarDescripcion] Roles del usuario:', [
                        'roles_ids' => $usuario->roles_ids,
                        'roles_count' => $rolesUsuario->count(),
                        'roles_data' => $rolesUsuario->get()->toArray(),
                    ]);

                    if ($rolesUsuario && $rolesUsuario->count() > 0) {
                        $rolUsuario = $rolesUsuario->first()->name ?? 'Sin rol';
                    }
                }

                \Log::info('[actualizarDescripcion] Registro de novedad:', [
                    'usuario_final' => $nombreUsuario,
                    'rol_final' => $rolUsuario,
                ]);

                $fechaActual = now()->format('d/m/Y H:i');
                $registroNovedad = "[{$nombreUsuario} - {$rolUsuario} - {$fechaActual}]\n{$justificacion}";

                if (!empty($novedadesActuales)) {
                    $pedido->novedades = $novedadesActuales . "\n\n" . $registroNovedad;
                } else {
                    $pedido->novedades = $registroNovedad;
                }
            }

            $pedido->save();

            return response()->json([
                'success' => true,
                'message' => 'Cambios guardados exitosamente',
                'data' => $pedido->toArray()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
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

            $nuevoEstado = $request->input('estado');

            $pedido = \App\Models\PedidoProduccion::find($id);
            if (!$pedido) {
                \Log::warning('[actualizarEstado] Pedido no encontrado', ['pedido_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            $estadoAnterior = $pedido->estado;

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

            $pedido->estado = $nuevoEstado;
            $pedido->save();

            if ($estadoAnterior !== $nuevoEstado) {
                $usuario = auth()->user();
                $nombreUsuario = $usuario ? $usuario->name : 'Sistema';

                $novedad = "Estado cambiado de '{$estadoAnterior}' a '{$nuevoEstado}' por {$nombreUsuario}";

                if (!empty($pedido->novedades)) {
                    $pedido->novedades .= "\n\n" . $novedad;
                } else {
                    $pedido->novedades = $novedad;
                }

                $pedido->save();

                \Log::info('[actualizarEstado] Novedad registrada', [
                    'pedido_id' => $id,
                    'novedad' => $novedad
                ]);
            }

            \Log::info('[actualizarEstado] Estado actualizado exitosamente', [
                'pedido_id' => $id,
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => $nuevoEstado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'data' => [
                    'id' => $pedido->id,
                    'estado' => $nuevoEstado,
                    'estado_anterior' => $estadoAnterior,
                    'novedades' => $pedido->novedades
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
     * POST /asesores/pedidos/confirm
     *
     * @deprecated Alias de confirmar(). Usar PATCH /api/pedidos/{id}/confirmar
     */
    public function confirm(Request $request): JsonResponse
    {
        $id = $request->input('pedido_id') ?: $request->route('id');

        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Se requiere el ID del pedido'
            ], 400);
        }

        return $this->confirmar($id);
    }

    /**
     * POST /asesores/pedidos/{id}/anular
     *
     * @deprecated Alias de cancelar(). Usar DELETE /api/pedidos/{id}/cancelar
     */
    public function anularPedido(Request $request, $id): JsonResponse
    {
        return $this->cancelar($id);
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
