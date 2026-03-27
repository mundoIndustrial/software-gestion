<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Asesores\UseCases\ConfirmarCorreccionPedidoUseCase;
use App\Application\Asesores\UseCases\EliminarBorradorAsesorUseCase;
use App\Application\Asesores\UseCases\ResolverPedidoIdAsesorUseCase;
use App\Application\Pedidos\DTOs\ActualizarProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\AnularProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\AgregarPrendaSimpleDTO;
use App\Application\Pedidos\UseCases\ActualizarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\AnularProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\AgregarPrendaSimpleUseCase;
use App\Infrastructure\Http\Requests\Asesores\ActualizarPedidoAsesorRequest;
use App\Infrastructure\Http\Requests\Asesores\AgregarPrendaSimpleAsesorRequest;
use App\Infrastructure\Http\Requests\Asesores\AnularPedidoAsesorRequest;
use App\Infrastructure\Http\Requests\Asesores\AnularPedidoNovedadAsesorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

final class AsesoresPedidosCommandController extends Controller
{
    public function __construct(
        private readonly ActualizarProduccionPedidoUseCase $actualizarProduccionPedidoUseCase,
        private readonly AnularProduccionPedidoUseCase $anularProduccionPedidoUseCase,
        private readonly ResolverPedidoIdAsesorUseCase $resolverPedidoIdAsesorUseCase,
        private readonly ConfirmarCorreccionPedidoUseCase $confirmarCorreccionPedidoUseCase,
        private readonly AgregarPrendaSimpleUseCase $agregarPrendaSimpleUseCase,
        private readonly EliminarBorradorAsesorUseCase $eliminarBorradorAsesorUseCase
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

    public function update(ActualizarPedidoAsesorRequest $request, int|string $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $dto = ActualizarProduccionPedidoDTO::fromRequest((string) $id, $validated);
            $this->actualizarProduccionPedidoUseCase->ejecutar($dto);

            return $this->json([
                'success' => true,
                'message' => 'Pedido actualizado exitosamente',
            ]);
        } catch (\Throwable $e) {
            return $this->failure('Error al actualizar el pedido.', 500);
        }
    }

    public function destroy(AnularPedidoAsesorRequest $request, int|string $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            $dto = AnularProduccionPedidoDTO::fromRequest((string) $id, $validated);
            $this->anularProduccionPedidoUseCase->ejecutarConDTO($dto);

            return $this->json([
                'success' => true,
                'message' => 'Pedido anulado exitosamente',
            ]);
        } catch (\Throwable $e) {
            return $this->failure('Error al anular el pedido.', 500);
        }
    }

    public function anularPedido(AnularPedidoNovedadAsesorRequest $request, int|string $id): JsonResponse
    {
        $validated = $request->validated();

        try {
            $usuario = Auth::user();
            $pedidoId = $this->resolverPedidoIdAsesorUseCase->ejecutar((string) $id, (int) ($usuario?->id ?? 0));

            $dto = AnularProduccionPedidoDTO::fromRequest((string) $pedidoId, [
                'razon' => $validated['novedad'],
                'nombreUsuario' => $usuario?->name ?? 'Sistema',
                'rolUsuario' => $usuario?->roles()->first()->name ?? 'Sin rol',
            ]);

            $pedidoAnulado = $this->anularProduccionPedidoUseCase->ejecutarConDTO($dto);

            return $this->json([
                'success' => true,
                'message' => 'Pedido anulado correctamente',
                'pedido' => $pedidoAnulado,
            ]);
        } catch (\Throwable $e) {
            \Log::error('[anularPedido] Error', ['error' => $e->getMessage()]);
            return $this->failure('Error al anular el pedido.', 500);
        }
    }

    public function confirmarCorreccion(Request $request, int|string $id): JsonResponse
    {
        try {
            $usuario = Auth::user();
            $pedidoId = $this->resolverPedidoIdAsesorUseCase->ejecutar((string) $id, (int) ($usuario?->id ?? 0));
            $resultado = $this->confirmarCorreccionPedidoUseCase->ejecutar($pedidoId, $usuario?->name ?? 'Sistema');

            return $this->json([
                'success' => true,
                'message' => 'Correccion confirmada. El pedido ha sido enviado a supervision.',
                'data' => [
                    'pedido_id' => $resultado['pedido_id'],
                    'numero_pedido' => $resultado['numero_pedido'],
                    'estado' => $resultado['estado'],
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('[confirmarCorreccion] Error', ['error' => $e->getMessage()]);
            return $this->failure('Error al confirmar correccion.', 500);
        }
    }

    public function agregarPrendaSimple(AgregarPrendaSimpleAsesorRequest $request, int|string $pedidoId): JsonResponse
    {
        try {
            $validated = $request->validated();

            $dto = AgregarPrendaSimpleDTO::fromRequest((string) $pedidoId, $validated);
            $resultado = $this->agregarPrendaSimpleUseCase->ejecutar($dto);

            return $this->json($resultado, 201);
        } catch (\Throwable $e) {
            \Log::error('[agregarPrendaSimple] Error', ['error' => $e->getMessage()]);
            return $this->json([
                'error' => 'Error agregando la prenda.',
            ], 400);
        }
    }

    public function destroyBorrador(Request $request, int|string $id)
    {
        try {
            $user = Auth::user();
            $this->eliminarBorradorAsesorUseCase->ejecutar((int) $id, (int) $user->id);

            return redirect()->back()->with('success', 'Borrador eliminado exitosamente');
        } catch (\Throwable $e) {
            \Log::error('[destroyBorrador] Error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error al eliminar el borrador.');
        }
    }
}
