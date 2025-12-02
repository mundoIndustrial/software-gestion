<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\PrendaCotizacion;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class CotizacionPrendaController extends Controller
{
    /**
     * Constructor: Verificar que el usuario sea Asesor
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            // Verificar rol: puede ser string o objeto
            $rol = is_object($user->role) ? $user->role->name : $user->role;
            
            if ($rol !== 'asesor') {
                abort(403, 'Solo asesores pueden crear cotizaciones de prenda');
            }
            return $next($request);
        });
    }

    /**
     * Mostrar formulario para crear cotización de prenda
     */
    public function create(): View
    {
        return view('cotizaciones.prenda.create');
    }

    /**
     * Guardar cotización de prenda en BORRADOR
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'asesora' => 'required|string|max:255',
                'fecha' => 'nullable|date',
                'action' => 'required|in:borrador,enviar',
                'tipo_cotizacion' => 'nullable|string',
                'productos' => 'nullable|array',
            ]);

            $esBorrador = $validated['action'] === 'borrador';

            // Crear cotización de prenda con tipo "P" (Prenda)
            $cotizacion = Cotizacion::create([
                'user_id' => auth()->id(),
                'numero_cotizacion' => 'COT-PREN-' . Str::random(8),
                'tipo_cotizacion_id' => 3, // Tipo "P" (Prenda) - Ajusta según tu BD
                'estado' => $esBorrador ? 'borrador' : 'enviada',
                'cliente' => $validated['cliente'],
                'asesora' => $validated['asesora'],
                'created_at' => $validated['fecha'] ?? now(),
                'es_borrador' => $esBorrador,
            ]);

            // Guardar detalles de prenda en prenda_cotizaciones
            if ($cotizacion) {
                PrendaCotizacion::create([
                    'cotizacion_id' => $cotizacion->id,
                    'tipo_cotizacion' => $validated['tipo_cotizacion'] ?? '',
                    'productos' => $validated['productos'] ?? [],
                ]);
            }

            $mensaje = $esBorrador 
                ? 'Cotización de prenda guardada en borrador' 
                : 'Cotización de prenda enviada correctamente';
            
            $redirect = $esBorrador 
                ? route('asesores.cotizaciones-prenda.edit', $cotizacion->id)
                : route('asesores.cotizaciones.index');

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'cotizacion_id' => $cotizacion->id,
                'redirect' => $redirect
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la cotización: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar cotización de prenda para edición
     */
    public function edit($id): View
    {
        $cotizacion = Cotizacion::find($id);
        
        if (!$cotizacion || $cotizacion->user_id !== auth()->id()) {
            abort(403);
        }

        return view('cotizaciones.prenda.edit', compact('cotizacion'));
    }

    /**
     * Actualizar cotización de prenda
     */
    public function update(Request $request, $id)
    {
        $cotizacion = Cotizacion::find($id);
        
        if (!$cotizacion || $cotizacion->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'asesora' => 'required|string|max:255',
                'action' => 'required|in:borrador,enviar',
                'tipo_cotizacion' => 'nullable|string',
                'productos' => 'nullable|array',
            ]);

            $esBorrador = $validated['action'] === 'borrador';

            $cotizacion->update([
                'cliente' => $validated['cliente'],
                'asesora' => $validated['asesora'],
                'estado' => $esBorrador ? 'borrador' : 'enviada',
                'es_borrador' => $esBorrador,
            ]);

            $prendaCotizacion = $cotizacion->prendaCotizacion;
            if ($prendaCotizacion) {
                $prendaCotizacion->update([
                    'tipo_cotizacion' => $validated['tipo_cotizacion'] ?? '',
                    'productos' => $validated['productos'] ?? [],
                ]);
            }

            $mensaje = $esBorrador 
                ? 'Cotización actualizada en borrador' 
                : 'Cotización actualizada y enviada';

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'redirect' => route('asesores.cotizaciones.index')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar cotizaciones de prenda
     */
    public function lista()
    {
        $cotizaciones = Cotizacion::where('user_id', auth()->id())
            ->where('tipo_cotizacion_id', 3) // Tipo Prenda
            ->orderBy('created_at', 'desc')
            ->get();

        return view('cotizaciones.prenda.lista', compact('cotizaciones'));
    }

    /**
     * Enviar cotización de prenda
     */
    public function enviar(Request $request, $id)
    {
        $cotizacion = Cotizacion::find($id);
        
        if (!$cotizacion || $cotizacion->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $cotizacion->update([
                'estado' => 'enviada',
                'es_borrador' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cotización enviada correctamente',
                'redirect' => route('asesores.cotizaciones.index')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar cotización de prenda
     */
    public function destroy($id)
    {
        $cotizacion = Cotizacion::find($id);
        
        if (!$cotizacion || $cotizacion->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $cotizacion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }
}
