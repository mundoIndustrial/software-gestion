<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContadorController extends Controller
{
    /**
     * Mostrar el dashboard del contador
     */
    public function index(): View
    {
        $cotizaciones = Cotizacion::where('es_borrador', false)
            ->orderBy('created_at', 'desc')
            ->get();
        return view('contador.index', compact('cotizaciones'));
    }

    /**
     * Obtener detalle de una cotización para el modal
     */
    public function getCotizacionDetail($id)
    {
        $cotizacion = Cotizacion::with([
            'prendas',
            'prendasCotizaciones',
            'logoCotizacion'
        ])->findOrFail($id);
        
        return view('contador.partials.cotizacion-modal', compact('cotizacion'));
    }

    /**
     * Eliminar una cotización completa
     */
    public function deleteCotizacion($id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            
            // Eliminar prendas relacionadas
            if ($cotizacion->prendasCotizaciones) {
                $cotizacion->prendasCotizaciones()->delete();
            }
            
            // Eliminar logo/cotización relacionada
            if ($cotizacion->logoCotizacion) {
                $cotizacion->logoCotizacion()->delete();
            }
            
            // Eliminar la cotización
            $cotizacion->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cotización: ' . $e->getMessage()
            ], 500);
        }
    }

}
