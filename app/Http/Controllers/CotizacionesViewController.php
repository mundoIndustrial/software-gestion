<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CotizacionesViewController extends Controller
{
    /**
     * Mostrar la vista de gestión de cotizaciones
     * Solo accesible para usuarios con rol 'supervisor-admin'
     * Filtra solo cotizaciones con estado 'entregar'
     */
    public function index(): View
    {
        $cotizaciones = Cotizacion::where('estado', 'entregar')
            ->where('es_borrador', false)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('cotizaciones.index', compact('cotizaciones'));
    }

    /**
     * Obtener detalle de una cotización para mostrar en modal
     */
    public function getCotizacionDetail($id)
    {
        try {
            $cotizacion = Cotizacion::with([
                'prendasCotizaciones',
                'logoCotizacion'
            ])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $cotizacion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la cotización: ' . $e->getMessage()
            ], 500);
        }
    }
}
