<?php

namespace App\Infrastructure\Http\Controllers\Pedidos;

use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Controllers\RegistroOrdenExceptionHandler;
use Illuminate\Http\Request;

// 🆕 FASE 6: UseCase Imports (Recibos Reflectivo)
use App\Application\UseCases\Pedidos\ObtenerRecibosReflectivoUseCase;
use App\Application\UseCases\Pedidos\DTOs\ObtenerRecibosReflectivoInput;

// 🆕 FASE 6 EXTENSIÓN: UseCase Imports (Recibos Reflectivo)
use App\Application\UseCases\Pedidos\ObtenerReciboReflectivoJsonUseCase;

/**
 * RecibosReflectivoController
 * 
 * Responsabilidad: Gestionar TODO relacionado con Recibos REFLECTIVO
 * - Listar recibos reflectivo
 * - Obtener recibo individual en JSON
 */
class RecibosReflectivoController extends Controller
{
    use RegistroOrdenExceptionHandler;

    // 🆕 FASE 6: UseCase Injections
    protected $obtenerRecibosReflectivoUseCase;

    // 🆕 FASE 6 EXTENSIÓN: UseCase Injections
    protected $obtenerReciboReflectivoJsonUseCase;

    public function __construct(
        // 🆕 FASE 6: UseCase Injections
        ObtenerRecibosReflectivoUseCase $obtenerRecibosReflectivoUseCase,
        // 🆕 FASE 6 EXTENSIÓN: UseCase Injections
        ObtenerReciboReflectivoJsonUseCase $obtenerReciboReflectivoJsonUseCase,
    )
    {
        // 🆕 FASE 6: UseCase Injections
        $this->obtenerRecibosReflectivoUseCase = $obtenerRecibosReflectivoUseCase;

        // 🆕 FASE 6 EXTENSIÓN: UseCase Injections
        $this->obtenerReciboReflectivoJsonUseCase = $obtenerReciboReflectivoJsonUseCase;
    }

    /**
     * 🆕 FASE 6: Mostrar recibos de reflectivo filtrados y enriquecidos
     * Delegado a: ObtenerRecibosReflectivoUseCase
     * 
     * GET /api/recibos-reflectivo
     */
    public function recibosReflectivo(Request $request)
    {
        return $this->tryExec(function() use ($request) {
            // 🆕 FASE 6: Usar ObtenerRecibosReflectivoUseCase
            $input = ObtenerRecibosReflectivoInput::fromRequest($request);
            $output = $this->obtenerRecibosReflectivoUseCase->execute($input);
            
            // Retornar según tipo de respuesta
            if ($input->es_ajax) {
                return response()->json($output->toJsonResponse());
            }
            
            return view('registros.recibos-reflectivo', $output->toViewData());
        });
    }

    /**
     * 🆕 FASE 6 EXTENSIÓN: Obtener datos de un recibo de REFLECTIVO como JSON
     * Delegado a: ObtenerReciboReflectivoJsonUseCase
     * 
     * GET /api/recibos-reflectivo/{reciboId}
     */
    public function getReciboReflectivoJson($reciboId)
    {
        return $this->tryExec(function() use ($reciboId) {
            $output = $this->obtenerReciboReflectivoJsonUseCase->execute((int) $reciboId);
            
            if (!$output['success']) {
                return response()->json(
                    ['success' => false, 'message' => $output['message']],
                    $output['http_code']
                );
            }

            return response()->json($output);
        });
    }
}
