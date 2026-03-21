<?php

namespace App\Infrastructure\Http\Controllers\Pedidos;

use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Controllers\RegistroOrdenExceptionHandler;
use Illuminate\Http\Request;

// 🆕 FASE 5: UseCase Imports (Entregas)
use App\Application\UseCases\Pedidos\ObtenerEntregasUseCase;
use App\Application\UseCases\Pedidos\GuardarDiaEntregaUseCase;
use App\Application\UseCases\Pedidos\DTOs\ObtenerEntregasInput;
use App\Application\UseCases\Pedidos\DTOs\GuardarDiaEntregaInput;

/**
 * PedidosEntregasController
 * 
 * Responsabilidad: Gestionar entregas de órdenes
 * - Obtener entregas
 * - Guardar día de entrega
 */
class PedidosEntregasController extends Controller
{
    use RegistroOrdenExceptionHandler;

    // 🆕 FASE 5: UseCase Injections
    protected $obtenerEntregasUseCase;
    protected $guardarDiaEntregaUseCase;

    public function __construct(
        // 🆕 FASE 5: UseCase Injections
        ObtenerEntregasUseCase $obtenerEntregasUseCase,
        GuardarDiaEntregaUseCase $guardarDiaEntregaUseCase,
    )
    {
        // 🆕 FASE 5: UseCase Injections
        $this->obtenerEntregasUseCase = $obtenerEntregasUseCase;
        $this->guardarDiaEntregaUseCase = $guardarDiaEntregaUseCase;
    }

    /**
     * 🆕 FASE 5: Obtener entregas de un pedido
     * Delegado a: ObtenerEntregasUseCase
     * 
     * GET /api/pedidos/{numero_pedido}/entregas
     */
    public function getEntregas($pedido)
    {
        return $this->tryExec(function() use ($pedido) {
            $input = ObtenerEntregasInput::fromNumeroPedido($pedido);
            $output = $this->obtenerEntregasUseCase->execute($input);
            return response()->json($output->toResponse());
        });
    }

    /**
     * 🆕 FASE 5: Guardar día de entrega y calcular fecha estimada
     * Delegado a: GuardarDiaEntregaUseCase
     * 
     * POST /api/pedidos/{id}/dia-entrega
     */
    public function saveDiaEntrega(Request $request, $id)
    {
        return $this->tryExec(function() use ($request, $id) {
            $input = GuardarDiaEntregaInput::fromRequest($request, $id);
            $output = $this->guardarDiaEntregaUseCase->execute($input);
            return response()->json($output->toResponse());
        });
    }
}
