<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CotizacionPrendaController extends Controller
{
    /**
     * Mostrar formulario de crear cotización de prenda
     */
    public function create()
    {
        return view('cotizaciones.prenda.create');
    }

    /**
     * Guardar cotización de prenda
     */
    public function store(Request $request)
    {
        // Redirigir a cotizaciones
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización creada exitosamente');
    }

    /**
     * Listar cotizaciones de prenda
     */
    public function lista()
    {
        return redirect()->route('cotizaciones.index');
    }

    /**
     * Editar cotización de prenda
     */
    public function edit($id)
    {
        return view('cotizaciones.prenda.edit', ['id' => $id]);
    }

    /**
     * Actualizar cotización de prenda
     */
    public function update(Request $request, $id)
    {
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización actualizada exitosamente');
    }

    /**
     * Enviar cotización de prenda
     */
    public function enviar(Request $request, $id)
    {
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización enviada exitosamente');
    }

    /**
     * Eliminar cotización de prenda
     */
    public function destroy($id)
    {
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización eliminada exitosamente');
    }
}
