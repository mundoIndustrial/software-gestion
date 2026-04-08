<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Application\Cotizacion\Services\CrearCotizacionBordadoService;
use App\Application\Cotizacion\Services\ActualizarBorradorCotizacionService;
use App\Application\Cotizacion\DTOs\CrearCotizacionBordadoRequest;
use App\Application\Cotizacion\DTOs\ActualizarBorradorCotizacionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Cotizacion;


/**
 * Controlador de Cotizaciones de Bordado
 * Responsabilidades:
 * - Orquestar peticiones HTTP
 * - Convertir requests a DTOs
 * - Delegar lógica a Application Services
 * - Formatear respuestas
 * Nota: La lógica de negocio está en Application Services,
 * no en el controlador (DDD - Domain-Driven Design)
 */
class CotizacionBordadoController extends Controller
{
    public function __construct(
        private readonly CrearCotizacionBordadoService $crearCotizacionService,
        private readonly ActualizarBorradorCotizacionService $actualizarBorradorService,
    ) {
    }

    /**
     * Mostrar formulario de crear cotización de bordado
     */
    public function create(Request $request)
    {
        $cotizacion = null;

        // Si hay parámetro editar, cargar datos del borrador
        if ($request->has('editar')) {
            $id = $request->input('editar');
            $cotizacion = Cotizacion::with([
                'cliente',
                'logoCotizacion',
                'logoCotizacion.fotos',
                'logoCotizacion.prendas.tipoLogo',
                'logoCotizacion.prendas.prendaCot.fotos',
                'logoCotizacion.prendas.fotos'
            ])->findOrFail($id);

            // Verificar que sea un borrador y del asesor autenticado
            $allowEditarCotizacionCreada = $request->boolean('editar_cotizacion');
            if ($cotizacion->asesor_id !== Auth::id()) {
                abort(403, 'No tienes permiso para editar esta cotización');
            }

            if (!$allowEditarCotizacionCreada && $cotizacion->es_borrador !== true) {
                abort(403, 'No tienes permiso para editar este borrador');
            }

            Log::info('📥 Cargando borrador para edición', [
                'cotizacion_id' => $id,
                'cliente_id' => $cotizacion->cliente_id,
                'cliente_nombre' => $cotizacion->cliente ? $cotizacion->cliente->nombre : 'NULL',
            ]);
        }

        return view('cotizaciones.bordado.create', [
            'cotizacion' => $cotizacion
        ]);
    }

    /**
     * Actualizar borrador de cotización de bordado
     */
    public function updateBorrador(Request $request, $id)
    {
        try {
            $id = (int) $id;

            // Crear DTO desde request
            $requestDTO = ActualizarBorradorCotizacionRequest::fromRequest($request, $id);

            // Delegar a Application Service
            $cotizacionResponse = $this->actualizarBorradorService->ejecutar($requestDTO);

            // Preparar mensaje según acción
            $mensaje = $requestDTO->es_envio
                ? 'Cotización enviada - Número: ' . ($cotizacionResponse->numero_cotizacion ?? 'N/A')
                : 'Borrador actualizado exitosamente';

            // Preparar redirección
            $redirect = route('asesores.cotizaciones.index')
                . '?'
                . http_build_query([
                    'tab' => $requestDTO->es_envio ? 'cotizaciones' : 'borradores',
                    'highlight' => $id,
                ]);

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => $cotizacionResponse->data,
                'redirect' => $redirect
            ]);

        } catch (\Exception $e) {
            Log::error('Error en updateBorrador', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $statusCode = match($e->getCode()) {
                403 => 403,
                422 => 422,
                default => 500,
            };

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * Guardar cotización de bordado
     */
    public function store(Request $request)
    {
        try {
            // Crear DTO desde request
            $requestDTO = CrearCotizacionBordadoRequest::fromRequest($request);

            // Delegar a Application Service
            $cotizacionResponse = $this->crearCotizacionService->ejecutar($requestDTO);

            // Preparar mensaje
            $mensaje = $cotizacionResponse->es_borrador
                ? 'Cotización guardada como borrador'
                : 'Cotización enviada - Número: ' . $cotizacionResponse->numero_cotizacion;

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => $cotizacionResponse->data,
                'logoCotizacionId' => null,
                'cotizacionId' => $cotizacionResponse->id,
                'redirect' => route('asesores.cotizaciones.index')
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error en store', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la cotización: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
