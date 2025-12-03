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
            
            // Convertir fotos y telas a URLs públicas
            if ($cotizacion->prendasCotizaciones) {
                foreach ($cotizacion->prendasCotizaciones as $prenda) {
                    // Convertir fotos a URLs
                    if ($prenda->fotos && is_array($prenda->fotos)) {
                        $prenda->fotos = array_map(function($foto) {
                            return is_string($foto) ? asset($foto) : $foto;
                        }, $prenda->fotos);
                    }
                    
                    // Convertir telas a URLs
                    if ($prenda->telas && is_array($prenda->telas)) {
                        $prenda->telas = array_map(function($tela) {
                            return is_string($tela) ? asset($tela) : $tela;
                        }, $prenda->telas);
                    }
                }
            }
            
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
