<?php

namespace App\Infrastructure\Http\Controllers\Cartera;

use App\Http\Controllers\Controller;
use App\Services\CarteraSugerenciasService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * CarteraSugerenciasController
 * 
 * Controlador centralizado para sugerencias de filtros en cartera
 * - pendiente_cartera
 * - RECHAZADO_CARTERA
 * - PENDIENTE_SUPERVISOR (aprobados)
 * - Anulada (anulados)
 */
class CarteraSugerenciasController extends Controller
{
    protected CarteraSugerenciasService $sugerenciasService;

    public function __construct(CarteraSugerenciasService $sugerenciasService)
    {
        $this->middleware('auth');
        $this->sugerenciasService = $sugerenciasService;
    }

    // ========================================
    // SUGERENCIAS PARA PENDIENTES (pendiente_cartera)
    // ========================================
    
    public function clientesPendientes(Request $request): JsonResponse
    {
        $busqueda = $request->input('busqueda', '');
        $clientes = $this->sugerenciasService->obtenerClientesSugerencias('pendiente_cartera', $busqueda);

        return response()->json([
            'success' => true,
            'sugerencias' => $clientes
        ]);
    }

    public function numerosPendientes(Request $request): JsonResponse
    {
        $busqueda = $request->input('busqueda', '');
        $numeros = $this->sugerenciasService->obtenerNumerosSugerencias('pendiente_cartera', $busqueda);

        return response()->json([
            'success' => true,
            'sugerencias' => $numeros
        ]);
    }

    public function fechasPendientes(Request $request): JsonResponse
    {
        $busqueda = $request->input('busqueda', '');
        $fechas = $this->sugerenciasService->obtenerFechasSugerencias('pendiente_cartera', $busqueda);

        return response()->json([
            'success' => true,
            'sugerencias' => $fechas
        ]);
    }

    // ========================================
    // SUGERENCIAS PARA RECHAZADOS (RECHAZADO_CARTERA)
    // ========================================

    public function clientesRechazados(Request $request): JsonResponse
    {
        $busqueda = $request->input('busqueda', '');
        $clientes = $this->sugerenciasService->obtenerClientesSugerencias('RECHAZADO_CARTERA', $busqueda);

        return response()->json([
            'success' => true,
            'sugerencias' => $clientes
        ]);
    }

    public function numerosRechazados(Request $request): JsonResponse
    {
        $busqueda = $request->input('busqueda', '');
        $numeros = $this->sugerenciasService->obtenerNumerosSugerencias('RECHAZADO_CARTERA', $busqueda);

        return response()->json([
            'success' => true,
            'sugerencias' => $numeros
        ]);
    }

    public function fechasRechazados(Request $request): JsonResponse
    {
        $busqueda = $request->input('busqueda', '');
        $fechas = $this->sugerenciasService->obtenerFechasSugerencias('RECHAZADO_CARTERA', $busqueda);

        return response()->json([
            'success' => true,
            'sugerencias' => $fechas
        ]);
    }

    // ========================================
    // SUGERENCIAS PARA APROBADOS (PENDIENTE_SUPERVISOR)
    // ========================================

    public function clientesAprobados(Request $request): JsonResponse
    {
        $busqueda = $request->input('busqueda', '');
        $clientes = $this->sugerenciasService->obtenerClientesSugerencias('PENDIENTE_SUPERVISOR', $busqueda);

        return response()->json([
            'success' => true,
            'sugerencias' => $clientes
        ]);
    }

    public function numerosAprobados(Request $request): JsonResponse
    {
        $busqueda = $request->input('busqueda', '');
        $numeros = $this->sugerenciasService->obtenerNumerosSugerencias('PENDIENTE_SUPERVISOR', $busqueda);

        return response()->json([
            'success' => true,
            'sugerencias' => $numeros
        ]);
    }

    public function fechasAprobados(Request $request): JsonResponse
    {
        $busqueda = $request->input('busqueda', '');
        $fechas = $this->sugerenciasService->obtenerFechasSugerencias('PENDIENTE_SUPERVISOR', $busqueda);

        return response()->json([
            'success' => true,
            'sugerencias' => $fechas
        ]);
    }

    // ========================================
    // SUGERENCIAS PARA ANULADOS (Anulada)
    // ========================================

    public function clientesAnulados(Request $request): JsonResponse
    {
        $busqueda = $request->input('busqueda', '');
        $clientes = $this->sugerenciasService->obtenerClientesSugerencias('Anulada', $busqueda);

        return response()->json([
            'success' => true,
            'sugerencias' => $clientes
        ]);
    }

    public function numerosAnulados(Request $request): JsonResponse
    {
        $busqueda = $request->input('busqueda', '');
        $numeros = $this->sugerenciasService->obtenerNumerosSugerencias('Anulada', $busqueda);

        return response()->json([
            'success' => true,
            'sugerencias' => $numeros
        ]);
    }

    public function fechasAnulados(Request $request): JsonResponse
    {
        $busqueda = $request->input('busqueda', '');
        $fechas = $this->sugerenciasService->obtenerFechasSugerencias('Anulada', $busqueda);

        return response()->json([
            'success' => true,
            'sugerencias' => $fechas
        ]);
    }
}
