<?php

namespace App\Http\Controllers\Asesores;

use App\Http\Controllers\Controller;
use App\Modules\Cotizaciones\Services\CotizacionFacadeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CotizacionesController - REFACTORIZADO
 * 
 * Responsabilidad única: Manejar solicitudes HTTP
 * Lógica de negocio delegada a servicios
 * Principio: Single Responsibility (SRP)
 */
class CotizacionesControllerRefactored extends Controller
{
    public function __construct(
        private CotizacionFacadeService $cotizacionService
    ) {}

    /**
     * Mostrar lista de cotizaciones y borradores
     * 
     * GET /asesores/cotizaciones
     */
    public function index()
    {
        $userId = Auth::id();

        // Obtener datos del servicio
        $allCotizaciones = $this->cotizacionService->getAllUserCotizaciones($userId);
        $allBorradores = $this->cotizacionService->getUserDrafts($userId);

        // Paginar
        $page = request()->get('page', 1);
        $perPage = 15;

        // Obtener por tipo
        $cotizacionesPrenda = $this->cotizacionService->getByType($userId, 'P', $page, $perPage);
        $cotizacionesLogo = $this->cotizacionService->getByType($userId, 'B', $page, $perPage);
        $cotizacionesPB = $this->cotizacionService->getByType($userId, 'PB', $page, $perPage);
        
        $borradorePrenda = $this->cotizacionService->getByType($userId, 'P', $page, $perPage);
        $borradoresLogo = $this->cotizacionService->getByType($userId, 'B', $page, $perPage);
        $borradores_PB = $this->cotizacionService->getByType($userId, 'PB', $page, $perPage);

        // Obtener todas con paginación
        $cotizacionesTodas = $this->cotizacionService->getByType($userId, 'todas', $page, $perPage);
        $borradoresTodas = collect($allBorradores)->slice(($page - 1) * $perPage, $perPage);

        // Logging
        \Log::info('CotizacionesController@index - Vista cargada', [
            'user_id' => $userId,
            'total_cotizaciones' => $allCotizaciones->count(),
            'total_borradores' => $allBorradores->count(),
        ]);

        return view('asesores.cotizaciones.index', compact(
            'cotizacionesPrenda',
            'cotizacionesLogo',
            'cotizacionesPB',
            'borradorePrenda',
            'borradoresLogo',
            'borradores_PB',
            'cotizacionesTodas',
            'borradoresTodas',
        ));
    }

    /**
     * Ver detalle de cotización
     * 
     * GET /asesores/cotizaciones/{id}
     */
    public function show($id)
    {
        $cotizacion = $this->cotizacionService->findById($id);

        if (!$cotizacion || $cotizacion->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para ver esta cotización');
        }

        return view('asesores.cotizaciones.show', compact('cotizacion'));
    }

    /**
     * Cambiar estado de cotización
     * 
     * PATCH /asesores/cotizaciones/{id}/estado
     */
    public function changeState(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|string|in:BORRADOR,ENVIADA_ASESOR,APROBADA_CONTADOR,RECHAZADA'
        ]);

        try {
            $cotizacion = $this->cotizacionService->changeState($id, $request->estado);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'data' => $this->cotizacionService->transform($cotizacion)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al cambiar estado', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado'
            ], 500);
        }
    }

    /**
     * Eliminar cotización
     * 
     * DELETE /asesores/cotizaciones/{id}
     */
    public function destroy($id)
    {
        try {
            $cotizacion = $this->cotizacionService->findById($id);

            if (!$cotizacion || $cotizacion->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cotización no encontrada'
                ], 404);
            }

            $this->cotizacionService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar cotización', [
                'cotizacion_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cotización'
            ], 500);
        }
    }
}
