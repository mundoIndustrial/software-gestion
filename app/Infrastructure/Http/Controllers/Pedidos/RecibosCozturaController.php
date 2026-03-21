<?php

namespace App\Infrastructure\Http\Controllers\Pedidos;

use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Controllers\RegistroOrdenExceptionHandler;
use Illuminate\Http\Request;
use App\Models\PedidoProduccion;

// 🆕 FASE 6: UseCase Imports (Recibos Costura)
use App\Application\UseCases\Pedidos\ObtenerRecibosCozturaUseCase;
use App\Application\UseCases\Pedidos\DTOs\ObtenerRecibosCozturaInput;

// 🆕 FASE 6 EXTENSIÓN: UseCase Imports (Recibos Costura secundarios)
use App\Application\UseCases\Pedidos\ObtenerReciboCozturaJsonUseCase;
use App\Application\UseCases\Pedidos\ContarRecibosEjecutandoUseCase;
use App\Application\UseCases\Pedidos\MarcarReciboVistoUseCase;
use App\Domain\Pedidos\Repositories\RecibosRepository;

/**
 * RecibosCozturaController
 * 
 * Responsabilidad: Gestionar TODO relacionado con Recibos de COSTURA
 * - Listar recibos costura
 * - Obtener recibo individual en JSON
 * - Obtener área más reciente
 * - Contar recibos ejecutando
 * - Marcar recibos como visto
 */
class RecibosCozturaController extends Controller
{
    use RegistroOrdenExceptionHandler;

    // 🆕 FASE 6: UseCase Injections (Recibos Costura)
    protected $obtenerRecibosCozturaUseCase;

    // 🆕 FASE 6 EXTENSIÓN: UseCase Injections (Recibos Costura secundarios)
    protected $obtenerReciboCozturaJsonUseCase;
    protected $contarRecibosEjecutandoUseCase;
    protected $marcarReciboVistoUseCase;
    protected $recibosRepository;

    public function __construct(
        // 🆕 FASE 6: UseCase Injections (Recibos Costura)
        ObtenerRecibosCozturaUseCase $obtenerRecibosCozturaUseCase,
        // 🆕 FASE 6 EXTENSIÓN: UseCase Injections (Recibos Costura secundarios)
        ObtenerReciboCozturaJsonUseCase $obtenerReciboCozturaJsonUseCase,
        ContarRecibosEjecutandoUseCase $contarRecibosEjecutandoUseCase,
        MarcarReciboVistoUseCase $marcarReciboVistoUseCase,
        RecibosRepository $recibosRepository,
    )
    {
        // 🆕 FASE 6: UseCase Injections
        $this->obtenerRecibosCozturaUseCase = $obtenerRecibosCozturaUseCase;

        // 🆕 FASE 6 EXTENSIÓN: UseCase Injections (5 métodos secundarios)
        $this->obtenerReciboCozturaJsonUseCase = $obtenerReciboCozturaJsonUseCase;
        $this->contarRecibosEjecutandoUseCase = $contarRecibosEjecutandoUseCase;
        $this->marcarReciboVistoUseCase = $marcarReciboVistoUseCase;
        $this->recibosRepository = $recibosRepository;
    }

    /**
     * 🆕 FASE 6: Mostrar recibos de costura filtrados y enriquecidos
     * Delegado a: ObtenerRecibosCozturaUseCase
     * 
     * GET /api/recibos-costura
     */
    public function recibosCostura(Request $request)
    {
        return $this->tryExec(function() use ($request) {
            // 🆕 FASE 6: Usar ObtenerRecibosCozturaUseCase
            $input = ObtenerRecibosCozturaInput::fromRequest($request);
            $output = $this->obtenerRecibosCozturaUseCase->execute($input);
            
            // Retornar según tipo de respuesta
            if ($input->es_ajax) {
                return response()->json($output->toJsonResponse());
            }
            
            return view('registros.recibos-costura', $output->toViewData());
        });
    }

    /**
     * 🆕 FASE 6 EXTENSIÓN: Obtener datos de un recibo de COSTURA como JSON
     * Delegado a: ObtenerReciboCozturaJsonUseCase
     * 
     * GET /api/recibos-costura/{reciboId}
     */
    public function getReciboJson($reciboId)
    {
        return $this->tryExec(function() use ($reciboId) {
            $output = $this->obtenerReciboCozturaJsonUseCase->execute((int) $reciboId);
            
            if (!$output['success']) {
                return response()->json(
                    ['success' => false, 'message' => $output['message']],
                    $output['http_code']
                );
            }

            return response()->json($output);
        });
    }

    /**
     * 🆕 FASE 6 EXTENSIÓN: Obtener el área más reciente de un pedido (API)
     * 
     * GET /api/pedidos/{id}/area-reciente
     */
    public function getAreaReciente($id)
    {
        return $this->tryExec(function() use ($id) {
            $pedido = PedidoProduccion::find($id);
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pedido no encontrado'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'area' => $pedido->area ?? 'Insumos',
                'pedido_id' => $id
            ]);
        });
    }

    /**
     * 🆕 FASE 6 EXTENSIÓN: Contar recibos que se están ejecutando en corte (API)
     * Delegado a: ContarRecibosEjecutandoUseCase
     * 
     * GET /api/recibos-costura/ejecutando-corte
     */
    public function contarRecibosEjecutandoCostura()
    {
        return $this->tryExec(function() {
            $output = $this->contarRecibosEjecutandoUseCase->execute(auth()->id());
            
            if (!$output['success']) {
                return response()->json(['success' => false, 'message' => $output['message']], $output['http_code']);
            }
            return response()->json($output);
        });
    }

    /**
     * 🆕 FASE 6 EXTENSIÓN: Marcar un recibo de COSTURA como visto por el usuario actual
     * Delegado a: MarcarReciboVistoUseCase
     * 
     * POST /api/recibos-costura/{id}/marcar-visto-corte
     */
    public function marcarReciboVistoCostura($reciboId)
    {
        return $this->tryExec(function() use ($reciboId) {
            $output = $this->marcarReciboVistoUseCase->execute((int) $reciboId, (int) auth()->id());
            
            if (!$output['success']) {
                return response()->json(['success' => false, 'message' => $output['message']], $output['http_code']);
            }
            return response()->json($output);
        });
    }
}
