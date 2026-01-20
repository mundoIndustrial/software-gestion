<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Application\Services\Recibos\ObtenerRecibosService;
use App\Application\Services\Recibos\GenerarPDFRecibosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * ReciboController - DELEGADOR PURO
 * 
 * Controlador para gestionar recibos de pedidos
 * Responsabilidad: HTTP layer solo, toda lógica en servicios
 */
class ReciboController extends Controller
{
    protected ObtenerRecibosService $obtenerRecibosService;
    protected GenerarPDFRecibosService $generarPDFService;

    public function __construct(
        ObtenerRecibosService $obtenerRecibosService,
        GenerarPDFRecibosService $generarPDFService
    ) {
        $this->obtenerRecibosService = $obtenerRecibosService;
        $this->generarPDFService = $generarPDFService;
    }

    /**
     * Mostrar recibo de un pedido - DELEGADO A SERVICIO
     */
    public function show($id)
    {
        try {
            $datos = $this->obtenerRecibosService->obtenerRecibo($id);

            return view('asesores.recibos.show', [
                'datos' => $datos,
                'pedido_id' => $id,
            ]);

        } catch (\Exception $e) {
            Log::error(' [RECIBO] Error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Obtener datos de recibo para vista previa (JSON) - DELEGADO A SERVICIO
     */
    public function datos($id)
    {
        try {
            $datos = $this->obtenerRecibosService->obtenerRecibo($id);

            return response()->json($datos);

        } catch (\Exception $e) {
            Log::error(' [RECIBO-JSON] Error', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Generar recibo en PDF - DELEGADO A SERVICIO
     */
    public function generarPDF($id)
    {
        try {
            $datos = $this->obtenerRecibosService->obtenerRecibo($id);
            
            // Generar y descargar PDF
            return $this->generarPDFService->generarPDF($datos, $id);

        } catch (\Exception $e) {
            Log::error(' [RECIBO-PDF] Error', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Listar recibos del asesor - DELEGADO A SERVICIO
     */
    public function index(Request $request)
    {
        try {
            $filtros = [
                'estado' => $request->get('estado'),
                'search' => $request->get('search'),
            ];

            $pedidos = $this->obtenerRecibosService->listarRecibos($filtros);
            $estados = $this->obtenerRecibosService->obtenerEstadosDisponibles();

            return view('asesores.recibos.index', compact('pedidos', 'estados'));

        } catch (\Exception $e) {
            Log::error(' [RECIBOS-INDEX] Error', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Obtener resumen de un recibo (JSON) - DELEGADO A SERVICIO
     */
    public function resumen($id)
    {
        try {
            $resumen = $this->obtenerRecibosService->obtenerResumen($id);

            return response()->json([
                'success' => true,
                'resumen' => $resumen,
            ]);

        } catch (\Exception $e) {
            Log::error(' [RECIBO-RESUMEN] Error', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Obtener procesos de una prenda del recibo (JSON) - DELEGADO A SERVICIO
     */
    public function procesos($pedidoId, $prendaId)
    {
        try {
            $procesos = $this->obtenerRecibosService->obtenerProcesosPrenda($pedidoId, $prendaId);

            return response()->json([
                'success' => true,
                'data' => $procesos,
            ]);

        } catch (\Exception $e) {
            Log::error(' [RECIBO-PROCESOS] Error', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
}
