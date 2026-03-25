<?php

namespace App\Infrastructure\Http\Controllers\Asesores\Pedidos;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\ObtenerDatosParaCrearPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerCotizacionesUseCase;
use App\Infrastructure\Http\Presenters\CrearPedidoPresenter;

/**
 * ObtenerPedidoFormDataController
 * 
 * ✅ RESPONSABILIDAD ÚNICA: Manejar vistas (GET) para formularios de creación de pedidos
 * 
 * HTTP Methods:
 * - GET /asesores/pedidos/crear-desde-cotizacion  → crearDesdeCotizacion()
 * - GET /asesores/pedidos/crear-nuevo             → crearNuevo()
 * 
 * Dependencias:
 * - ObtenerDatosParaCrearPedidoUseCase: Obtener datos compartidos
 * - ObtenerCotizacionesUseCase: Obtener cotizaciones
 * - CrearPedidoPresenter: Formatear datos para Blade
 * 
 * Características:
 * ✅ Solo 3 dependencias (antes: 21)
 * ✅ Solo orquesta obtención de datos
 * ✅ Presenter maneja formateo para vista
 * ✅ Fácil agregar nuevas vistas
 */
class ObtenerPedidoFormDataController extends Controller
{
    public function __construct(
        private ObtenerDatosParaCrearPedidoUseCase $obtenerDatosUseCase,
        private ObtenerCotizacionesUseCase $obtenerCotizacionesUseCase,
        private CrearPedidoPresenter $presenter,
    ) {}

    /**
     * GET /asesores/pedidos/crear-desde-cotizacion
     * 
     * Mostrar formulario para crear pedido desde una cotización existente
     * Precarga datos de cotizaciones para el usuario
     * 
     * @return View
     */
    public function crearDesdeCotizacion(Request $request): View
    {
        try {
            $usuarioId = Auth::id();

            Log::info('[ObtenerPedidoFormDataController::crearDesdeCotizacion] Iniciado', [
                'usuario_id' => $usuarioId,
            ]);

            // 1. Obtener datos compartidos (UseCase)
            $datosCompartidos = $this->obtenerDatosUseCase->ejecutar($usuarioId);

            // 2. Obtener cotizaciones (UseCase)
            $cotizaciones = $this->obtenerCotizacionesUseCase->ejecutar($usuarioId);

            // 3. Formatear para Blade (Presenter)
            $datosVista = $this->presenter->prepararParaVista(
                $datosCompartidos,
                $cotizaciones,
                modoEdicion: false
            );

            Log::info('[ObtenerPedidoFormDataController::crearDesdeCotizacion] Completado', [
                'usuario_id' => $usuarioId,
                'cotizaciones' => $cotizaciones->count(),
            ]);

            return view('asesores.pedidos.crear-pedido-desde-cotizacion', $datosVista);

        } catch (\Exception $e) {
            Log::error('[ObtenerPedidoFormDataController::crearDesdeCotizacion] Error', [
                'usuario_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('errors.500', ['error' => $e->getMessage()]);
        }
    }

    /**
     * GET /asesores/pedidos/crear-nuevo
     * 
     * Mostrar formulario para crear pedido nuevo (desde cero)
     * Precarga datos compartidos pero SIN cotizaciones
     * 
     * @return View
     */
    public function crearNuevo(Request $request): View
    {
        try {
            $usuarioId = Auth::id();

            Log::info('[ObtenerPedidoFormDataController::crearNuevo] Iniciado', [
                'usuario_id' => $usuarioId,
            ]);

            // 1. Obtener datos compartidos (UseCase)
            $datosCompartidos = $this->obtenerDatosUseCase->ejecutar($usuarioId);

            // 2. Obtener datos de edición si es aplicable
            $editId = $request->query('edit') ? (int) $request->query('edit') : null;
            $datosEdicion = [];
            $modoEdicion = false;

            if ($editId) {
                // TODO: Crear UseCase para obtener datos de edición
                $modoEdicion = true;
            }

            // 3. Formatear para Blade (Presenter)
            $datosVista = $this->presenter->prepararParaVista(
                $datosCompartidos,
                cotizaciones: null,
                modoEdicion: $modoEdicion,
                datosEdicion: $datosEdicion
            );

            Log::info('[ObtenerPedidoFormDataController::crearNuevo] Completado', [
                'usuario_id' => $usuarioId,
                'modo_edicion' => $modoEdicion,
            ]);

            return view('asesores.pedidos.crear-pedido-nuevo', $datosVista);

        } catch (\Exception $e) {
            Log::error('[ObtenerPedidoFormDataController::crearNuevo] Error', [
                'usuario_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('errors.500', ['error' => $e->getMessage()]);
        }
    }
}
