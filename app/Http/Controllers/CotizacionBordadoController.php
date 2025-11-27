<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\LogoCotizacion;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class CotizacionBordadoController extends Controller
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
                abort(403, 'Solo asesores pueden crear cotizaciones de bordado');
            }
            return $next($request);
        });
    }

    /**
     * Mostrar formulario para crear cotización de bordado
     */
    public function create(): View
    {
        return view('cotizaciones.bordado.create');
    }

    /**
     * Guardar cotización de bordado en BORRADOR
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'asesora' => 'required|string|max:255',
                'tecnicas' => 'nullable|array',
                'imagenes' => 'nullable|array',
                'observaciones_tecnicas' => 'nullable|string',
                'ubicaciones' => 'nullable|array',
                'observaciones_generales' => 'nullable|array',
            ]);

            // Crear cotización de bordado en BORRADOR
            $cotizacion = Cotizacion::create([
                'user_id' => auth()->id(),
                'numero_cotizacion' => 'COT-BORD-' . Str::random(8),
                'tipo_cotizacion' => 'bordado',
                'estado' => 'borrador',
                'cliente' => $validated['cliente'],
                'asesora' => $validated['asesora'],
                'es_borrador' => true,
            ]);

            // Guardar detalles técnicos en logo_cotizaciones
            if ($cotizacion) {
                LogoCotizacion::create([
                    'cotizacion_id' => $cotizacion->id,
                    'tecnicas' => $validated['tecnicas'] ?? [],
                    'imagenes' => $validated['imagenes'] ?? [],
                    'observaciones_tecnicas' => $validated['observaciones_tecnicas'] ?? '',
                    'ubicaciones' => $validated['ubicaciones'] ?? [],
                    'observaciones_generales' => $validated['observaciones_generales'] ?? [],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Cotización de bordado guardada en borrador',
                'cotizacion_id' => $cotizacion->id,
                'redirect' => route('asesores.cotizaciones-bordado.edit', $cotizacion->id)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar cotización: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar formulario para editar cotización de bordado
     */
    public function edit(Cotizacion $cotizacion): View
    {
        // Verificar que sea del usuario actual y sea de tipo bordado
        if ($cotizacion->user_id !== auth()->id() || $cotizacion->tipo_cotizacion !== 'bordado') {
            abort(403, 'No tienes permiso para editar esta cotización');
        }

        return view('cotizaciones.bordado.edit', compact('cotizacion'));
    }

    /**
     * Actualizar cotización de bordado
     */
    public function update(Request $request, Cotizacion $cotizacion)
    {
        try {
            // Verificar permisos
            if ($cotizacion->user_id !== auth()->id() || $cotizacion->tipo_cotizacion !== 'bordado') {
                abort(403, 'No tienes permiso para actualizar esta cotización');
            }

            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'asesora' => 'required|string|max:255',
                'tecnicas' => 'nullable|array',
                'imagenes' => 'nullable|array',
                'observaciones_tecnicas' => 'nullable|string',
                'ubicaciones' => 'nullable|array',
                'observaciones_generales' => 'nullable|array',
            ]);

            // Actualizar cotización
            $cotizacion->update([
                'cliente' => $validated['cliente'],
                'asesora' => $validated['asesora'],
            ]);

            // Actualizar o crear logo_cotizaciones
            LogoCotizacion::updateOrCreate(
                ['cotizacion_id' => $cotizacion->id],
                [
                    'tecnicas' => $validated['tecnicas'] ?? [],
                    'imagenes' => $validated['imagenes'] ?? [],
                    'observaciones_tecnicas' => $validated['observaciones_tecnicas'] ?? '',
                    'ubicaciones' => $validated['ubicaciones'] ?? [],
                    'observaciones_generales' => $validated['observaciones_generales'] ?? [],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Cotización actualizada'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Enviar cotización de bordado (crear pedido)
     */
    public function enviar(Request $request, Cotizacion $cotizacion)
    {
        try {
            // Verificar permisos y que sea borrador
            if ($cotizacion->user_id !== auth()->id() || $cotizacion->tipo_cotizacion !== 'bordado') {
                abort(403, 'No tienes permiso');
            }

            if ($cotizacion->estado !== 'borrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden enviar cotizaciones en borrador'
                ], 400);
            }

            // Marcar como enviada
            $cotizacion->update([
                'estado' => 'enviada',
                'es_borrador' => false,
                'fecha_envio' => now()
            ]);

            // Crear pedido de producción basado en esta cotización
            $pedido = PedidoProduccion::create([
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'asesor_id' => auth()->id(),
                'area' => 'Bordado', // Área específica para bordados
                'estado' => 'pendiente'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cotización enviada y pedido creado',
                'pedido_id' => $pedido->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todas las cotizaciones de bordado del usuario
     */
    public function lista(): View
    {
        $cotizaciones = Cotizacion::where('user_id', auth()->id())
            ->where('tipo_cotizacion', 'bordado')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('cotizaciones.bordado.lista', compact('cotizaciones'));
    }

    /**
     * Eliminar cotización de bordado (solo si es borrador)
     */
    public function destroy(Cotizacion $cotizacion)
    {
        try {
            if ($cotizacion->user_id !== auth()->id() || $cotizacion->tipo_cotizacion !== 'bordado') {
                abort(403, 'No tienes permiso');
            }

            if ($cotizacion->estado !== 'borrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo puedes eliminar cotizaciones en borrador'
                ], 400);
            }

            $cotizacion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar: ' . $e->getMessage()
            ], 500);
        }
    }
}
