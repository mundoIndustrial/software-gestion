<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\PedidoProduccion;
use App\Models\LogoCotizacion;
use App\Services\ImagenCotizacionService;
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
            // Convertir JSON strings a arrays si es necesario
            $tecnicas = $request->input('tecnicas');
            if (is_string($tecnicas)) {
                $tecnicas = json_decode($tecnicas, true) ?? [];
            }
            
            $ubicaciones = $request->input('ubicaciones');
            if (is_string($ubicaciones)) {
                $ubicaciones = json_decode($ubicaciones, true) ?? [];
            }
            
            $observacionesGenerales = $request->input('observaciones_generales');
            if (is_string($observacionesGenerales)) {
                $observacionesGenerales = json_decode($observacionesGenerales, true) ?? [];
            }
            
            $validated = $request->validate([
                'cliente' => 'required|string|max:255',
                'asesora' => 'required|string|max:255',
                'fecha' => 'nullable|date',
                'action' => 'required|in:borrador,enviar',
                'observaciones_tecnicas' => 'nullable|string',
            ]);

            $esBorrador = $validated['action'] === 'borrador';
            
            // Para cotización de bordado/logo, el tipo es B
            $codigoTipoCotizacion = 'B';
            
            // Obtener tipo_cotizacion_id buscando el código
            $tipoCotizacion = \App\Models\TipoCotizacion::where('codigo', $codigoTipoCotizacion)->first();
            $tipoCotizacionId = $tipoCotizacion?->id;
            
            \Log::info('CotizacionBordadoController - Tipo cotización detectado', [
                'codigo' => $codigoTipoCotizacion,
                'tipo_cotizacion_id' => $tipoCotizacionId,
                'nombre' => $tipoCotizacion?->nombre
            ]);

            // Crear cotización de bordado/logo (SIN tipo_venta, ya que no es necesario)
            $cotizacion = Cotizacion::create([
                'user_id' => auth()->id(),
                'numero_cotizacion' => 'COT-BORD-' . Str::random(8),
                'tipo_cotizacion_id' => $tipoCotizacionId,
                'tipo_venta' => null,  // No se guarda tipo_venta para bordado/logo
                'estado' => $esBorrador ? 'BORRADOR' : 'ENVIADA_CONTADOR',
                'cliente' => $validated['cliente'],
                'asesora' => $validated['asesora'],
                'fecha_inicio' => $validated['fecha'] ?? now(),
                'es_borrador' => $esBorrador,
            ]);

            // Guardar detalles técnicos en logo_cotizaciones
            if ($cotizacion) {
                // Procesar imágenes usando el servicio
                $imagenes = [];
                if ($request->hasFile('imagenes')) {
                    $imagenService = new ImagenCotizacionService();
                    foreach ($request->file('imagenes') as $archivo) {
                        $ruta = $imagenService->guardarImagen($cotizacion->id, $archivo, 'logo');
                        if ($ruta) {
                            $imagenes[] = $ruta;
                        }
                    }
                }

                LogoCotizacion::create([
                    'cotizacion_id' => $cotizacion->id,
                    'tecnicas' => $tecnicas,
                    'imagenes' => $imagenes,
                    'observaciones_tecnicas' => $validated['observaciones_tecnicas'] ?? '',
                    'ubicaciones' => $ubicaciones,
                    'observaciones_generales' => $observacionesGenerales,
                ]);
            }

            $mensaje = $esBorrador 
                ? 'Cotización de bordado guardada en borrador' 
                : 'Cotización de bordado enviada correctamente';
            
            $redirect = $esBorrador 
                ? route('cotizaciones-bordado.edit', $cotizacion->id)
                : route('asesores.cotizaciones.index');

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'cotizacion_id' => $cotizacion->id,
                'redirect' => $redirect
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', array_map(fn($msgs) => implode(', ', $msgs), $e->errors())),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al guardar cotización de bordado', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar cotización: ' . $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Mostrar formulario para editar cotización de bordado
     */
    public function edit(Cotizacion $cotizacion): View
    {
        // Verificar que sea del usuario actual
        if ($cotizacion->user_id !== auth()->id()) {
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
            if ($cotizacion->user_id !== auth()->id()) {
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
            // Verificar permisos
            if ($cotizacion->user_id !== auth()->id()) {
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
                'estado' => 'ENVIADA_CONTADOR',
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
            ->where('tipo_cotizacion_id', 2) // Tipo bordado
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
            if ($cotizacion->user_id !== auth()->id()) {
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
