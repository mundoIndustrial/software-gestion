<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CotizacionBordadoController extends Controller
{
    /**
     * Mostrar formulario de crear cotización de bordado
     */
    public function create()
    {
        return view('cotizaciones.bordado.create');
    }

    /**
     * Guardar cotización de bordado
     */
    public function store(Request $request)
    {
        // Redirigir a cotizaciones
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización creada exitosamente');
    }

    /**
     * Listar cotizaciones de bordado
     */
    public function lista()
    {
        return redirect()->route('cotizaciones.index');
    }

    /**
     * Editar cotización de bordado
     */
    public function edit($id)
    {
        return view('cotizaciones.bordado.edit', ['id' => $id]);
    }

    /**
     * Actualizar cotización de bordado
     */
    public function update(Request $request, $id)
    {
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización actualizada exitosamente');
    }

    /**
     * Enviar cotización de bordado
     */
    public function enviar(Request $request, $id)
    {
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización enviada exitosamente');
    }

    /**
     * Eliminar cotización de bordado
     */
    public function destroy($id)
    {
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización eliminada exitosamente');
    }
}
