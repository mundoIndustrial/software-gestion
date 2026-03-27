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

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'cliente' => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'novedades' => 'nullable|string',
            'forma_de_pago' => 'nullable|string|max:69',
            'estado' => 'nullable|string|in:Pendiente,Entregado,En Ejecución,No iniciado,Anulada,PENDIENTE_SUPERVISOR',
            'area' => 'nullable|string|max:255',
            'prendas' => 'sometimes|array',
            'prendas.*.id' => 'nullable|exists:prendas_pedido,id',
            'prendas.*.nombre_prenda' => 'required_with:prendas|string',
            'prendas.*.talla' => 'nullable|string',
            'prendas.*.cantidad' => 'required_with:prendas|integer|min:1',
            'prendas.*.precio_unitario' => 'nullable|numeric|min:0',
            'epp' => 'sometimes|array',
            'epp.*.id' => 'required_with:epp|integer|exists:pedido_epp,id',
            'epp.*.cantidad' => 'required_with:epp|integer|min:0',
            'epp.*.observaciones' => 'nullable|string',
        ]);

        try {
            $dto = ActualizarProduccionPedidoDTO::fromRequest((string) $id, $validated);
            $this->actualizarProduccionPedidoUseCase->ejecutar($dto);

            return response()->json([
                'success' => true,
                'message' => 'Pedido actualizado exitosamente',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pedido.',
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'razon' => 'required|string|max:500',
            ]);

            $dto = AnularProduccionPedidoDTO::fromRequest((string) $id, $validated);
            $this->anularProduccionPedidoUseCase->ejecutarConDTO($dto);

            return response()->json([
                'success' => true,
                'message' => 'Pedido anulado exitosamente',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al anular el pedido.',
            ], 500);
        }
    }

    public function anularPedido(Request $request, $id)
    {
        $validated = $request->validate([
            'novedad' => 'required|string|min:10|max:500',
        ], [
            'novedad.required' => 'La novedad es obligatoria',
            'novedad.min' => 'La novedad debe tener al menos 10 caracteres',
            'novedad.max' => 'La novedad no puede exceder 500 caracteres',
        ]);

        try {
            $usuario = Auth::user();
            $pedidoId = $this->resolverPedidoIdAsesorUseCase->ejecutar((string) $id, (int) ($usuario?->id ?? 0));

            $dto = AnularProduccionPedidoDTO::fromRequest((string) $pedidoId, [
                'razon' => $validated['novedad'],
                'nombreUsuario' => $usuario?->name ?? 'Sistema',
                'rolUsuario' => $usuario?->roles()->first()->name ?? 'Sin rol',
            ]);

            $pedidoAnulado = $this->anularProduccionPedidoUseCase->ejecutarConDTO($dto);

            return response()->json([
                'success' => true,
                'message' => 'Pedido anulado correctamente',
                'pedido' => $pedidoAnulado,
            ]);
        } catch (\Throwable $e) {
            \Log::error('[anularPedido] Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al anular el pedido.',
            ], 500);
        }
    }

    public function confirmarCorreccion(Request $request, $id)
    {
        try {
            $usuario = Auth::user();
            $pedidoId = $this->resolverPedidoIdAsesorUseCase->ejecutar((string) $id, (int) ($usuario?->id ?? 0));
            $resultado = $this->confirmarCorreccionPedidoUseCase->ejecutar($pedidoId, $usuario?->name ?? 'Sistema');

            return response()->json([
                'success' => true,
                'message' => 'Corrección confirmada. El pedido ha sido enviado a supervisión.',
                'data' => [
                    'pedido_id' => $resultado['pedido_id'],
                    'numero_pedido' => $resultado['numero_pedido'],
                    'estado' => $resultado['estado'],
                ],
            ]);
        } catch (\Throwable $e) {
            \Log::error('[confirmarCorreccion] Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar corrección.',
            ], 500);
        }
    }

    public function agregarPrendaSimple(Request $request, $pedidoId)
    {
        try {
            $validated = $request->validate([
                'nombre_prenda' => 'required|string|max:255',
                'cantidad' => 'required|integer|min:1',
                'descripcion' => 'nullable|string|max:1000',
            ]);

            $dto = AgregarPrendaSimpleDTO::fromRequest((string) $pedidoId, $validated);
            $resultado = $this->agregarPrendaSimpleUseCase->ejecutar($dto);

            return response()->json($resultado, 201);
        } catch (\Throwable $e) {
            \Log::error('[agregarPrendaSimple] Error', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Error agregando la prenda.',
            ], 400);
        }
    }

    public function destroyBorrador(Request $request, $id)
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

