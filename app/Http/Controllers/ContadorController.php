<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\PrendaCotizacionFriendly;
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

    /**
     * Guardar notas de tallas para una prenda
     */
    public function guardarNotasTallas($prendaId, Request $request)
    {
        try {
            $prenda = PrendaCotizacionFriendly::findOrFail($prendaId);
            
            // Validar que se envíe el texto de notas
            $request->validate([
                'notas' => 'required|string'
            ]);
            
            // Guardar las notas
            $prenda->notas_tallas = $request->input('notas');
            $prenda->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Notas de tallas guardadas correctamente',
                'notas' => $prenda->notas_tallas
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar las notas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar el estado de una cotización
     */
    public function cambiarEstado($id, Request $request)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            
            // Validar que el estado sea uno de los permitidos
            $request->validate([
                'estado' => 'required|in:enviada,entregar,anular'
            ]);
            
            // Actualizar el estado
            $cotizacion->estado = $request->input('estado');
            $cotizacion->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'estado' => $cotizacion->estado
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

}
