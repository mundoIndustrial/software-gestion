<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CotizacionPrendaController
 * 
 * Controlador que gestiona las cotizaciones tipo PRENDA
 * Delega la mayor parte de la lógica al CotizacionesController
 */
class CotizacionPrendaController extends Controller
{
    /**
     * Mostrar formulario para crear cotización de prenda
     */
    public function create()
    {
        return view('cotizaciones.prenda.create');
    }

    /**
     * Guardar nueva cotización de prenda
     */
    public function store(Request $request)
    {
        // Agregar el código de tipo de cotización (P = Prenda)
        $request->merge([
            'tipo_cotizacion' => 'P',
            'tipo' => $request->input('action', 'borrador') === 'enviar' ? 'enviada' : 'borrador'
        ]);
        
        // Delegamos al controlador de asesores que maneja todo
        $controller = new \App\Http\Controllers\Asesores\CotizacionesController(
            app(\App\Services\CotizacionService::class),
            app(\App\Services\ImagenCotizacionService::class),
            app(\App\Services\PedidoService::class),
            app(\App\Services\FormatterService::class)
        );
        
        return $controller->guardar($request);
    }

    /**
     * Mostrar lista de cotizaciones de prenda
     */
    public function lista()
    {
        $cotizaciones = Cotizacion::where('user_id', Auth::id())
            ->whereHas('tipoCotizacion', fn($q) => $q->where('codigo', 'P'))
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('cotizaciones.prenda.lista', compact('cotizaciones'));
    }

    /**
     * Mostrar formulario para editar cotización de prenda
     */
    public function edit(Cotizacion $cotizacion)
    {
        $this->authorize('update', $cotizacion);
        return view('cotizaciones.prenda.edit', compact('cotizacion'));
    }

    /**
     * Actualizar cotización de prenda
     */
    public function update(Request $request, Cotizacion $cotizacion)
    {
        $this->authorize('update', $cotizacion);

        $controller = new \App\Http\Controllers\Asesores\CotizacionesController(
            app(\App\Services\CotizacionService::class),
            app(\App\Services\ImagenCotizacionService::class),
            app(\App\Services\PedidoService::class),
            app(\App\Services\FormatterService::class)
        );
        
        return $controller->guardar($request);
    }

    /**
     * Enviar cotización de prenda
     */
    public function enviar(Request $request, Cotizacion $cotizacion)
    {
        $this->authorize('update', $cotizacion);

        $request->merge(['cotizacion_id' => $cotizacion->id, 'tipo' => 'enviada']);
        
        $controller = new \App\Http\Controllers\Asesores\CotizacionesController(
            app(\App\Services\CotizacionService::class),
            app(\App\Services\ImagenCotizacionService::class),
            app(\App\Services\PedidoService::class),
            app(\App\Services\FormatterService::class)
        );
        
        return $controller->guardar($request);
    }

    /**
     * Eliminar cotización de prenda
     */
    public function destroy(Cotizacion $cotizacion)
    {
        $this->authorize('delete', $cotizacion);

        $controller = new \App\Http\Controllers\Asesores\CotizacionesController(
            app(\App\Services\CotizacionService::class),
            app(\App\Services\ImagenCotizacionService::class),
            app(\App\Services\PedidoService::class),
            app(\App\Services\FormatterService::class)
        );
        
        return $controller->destroy($cotizacion->id);
    }
}
