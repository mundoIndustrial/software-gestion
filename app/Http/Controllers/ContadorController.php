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
        $cotizaciones = Cotizacion::orderBy('fecha', 'desc')->get();
        return view('contador.index', compact('cotizaciones'));
    }

    /**
     * Obtener detalle de una cotización para el modal
     */
    public function getCotizacionDetail($id)
    {
        $cotizacion = Cotizacion::with('prendas')->findOrFail($id);
        
        return view('contador.partials.cotizacion-modal', compact('cotizacion'));
    }

    /**
     * Obtener modal para cotizar prendas
     */
    public function getCotizarPrendasModal($id)
    {
        $cotizacion = Cotizacion::with('prendas')->findOrFail($id);
        
        return view('contador.partials.cotizar-prendas-modal', compact('cotizacion'));
    }
}
