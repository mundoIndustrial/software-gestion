<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        // Usar transacción para garantizar atomicidad
        // Si algo falla, TODO se revierte (ROLLBACK)
        return DB::transaction(function () use ($request) {
            try {
                Log::info('🔵 CotizacionPrendaController@store - Iniciando guardado de cotización de Prenda');

                // Determinar si es borrador o enviada
                $action = $request->input('action') ?? $request->input('accion');
                $esBorrador = $action === 'borrador';
                $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA';

                // Obtener o crear cliente
                $clienteId = $request->input('cliente_id');
                $nombreCliente = $request->input('cliente');

                if ($nombreCliente && !$clienteId) {
                    $cliente = \App\Models\Cliente::firstOrCreate(
                        ['nombre' => $nombreCliente],
                        ['nombre' => $nombreCliente]
                    );
                    $clienteId = $cliente->id;
                }

                // Crear cotización (sin número si es borrador)
                $cotizacion = Cotizacion::create([
                    'asesor_id' => Auth::id(),
                    'cliente_id' => $clienteId,
                    'numero_cotizacion' => null, // Se genera en el job si se envía
                    'tipo_cotizacion_id' => 3, // Cotización de Prenda
                    'tipo_venta' => $request->input('tipo_venta', 'M'),
                    'es_borrador' => $esBorrador,
                    'estado' => $estado,
                    'productos' => json_encode($request->input('prendas', [])),
                    'tecnicas' => json_encode($request->input('tecnicas', [])),
                    'ubicaciones' => json_encode($request->input('ubicaciones', [])),
                    'observaciones_tecnicas' => $request->input('observaciones_tecnicas', ''),
                    'observaciones_generales' => json_encode($request->input('observaciones_generales', [])),
                    'especificaciones' => json_encode($request->input('especificaciones', [])),
                    'imagenes' => json_encode($request->input('imagenes', [])),
                ]);

                Log::info('✅ Cotización de Prenda creada', [
                    'cotizacion_id' => $cotizacion->id,
                    'es_borrador' => $esBorrador,
                    'estado' => $estado,
                    'cliente_id' => $clienteId,
                ]);

                // Si se envía (no es borrador), encolar el job para generar número
                if (!$esBorrador) {
                    \App\Jobs\ProcesarEnvioCotizacionJob::dispatch(
                        $cotizacion->id,
                        3 // tipo_cotizacion_id para Prenda
                    )->onQueue('cotizaciones');

                    Log::info('📋 Job de envío encolado', [
                        'cotizacion_id' => $cotizacion->id,
                        'queue' => 'cotizaciones'
                    ]);
                }

                // Procesar imágenes si existen
                if ($request->hasFile('prendas')) {
                    $this->procesarImagenesCotizacion($request, $cotizacion->id);
                }

                // Recargar la cotización con todas sus relaciones
                $cotizacionCompleta = Cotizacion::with([
                    'cliente',
                    'prendas.fotos',
                    'prendas.telaFotos',
                    'prendas.tallas',
                    'prendas.variantes.manga',
                    'prendas.variantes.broche',
                    'logoCotizacion.fotos'
                ])->findOrFail($cotizacion->id);

                return response()->json([
                    'success' => true,
                    'message' => $esBorrador ? 'Cotización guardada como borrador' : 'Cotización enviada (procesando número)',
                    'data' => $cotizacionCompleta->toArray(),
                    'redirect' => route('asesores.cotizaciones.index')
                ], 201);

            } catch (\Exception $e) {
                Log::error('❌ Error al guardar cotización de Prenda', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                // La transacción se revierte automáticamente
                // Nada se guarda en la BD
                throw $e;
            }
        }, attempts: 3); // Reintentar hasta 3 veces si hay deadlock
    }

    /**
     * Listar cotizaciones de prenda
     */
    public function lista()
    {
        return redirect()->route('asesores.cotizaciones.index');
    }

    /**
     * Editar cotización de prenda
     */
    public function edit($id)
    {
        $cotizacion = Cotizacion::findOrFail($id);
        return view('cotizaciones.prenda.create', ['cotizacion' => $cotizacion]);
    }

    /**
     * Actualizar cotización de prenda
     */
    public function update(Request $request, $id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            
            $cotizacion->update([
                'productos' => json_encode($request->input('prendas', [])),
                'tecnicas' => json_encode($request->input('tecnicas', [])),
                'ubicaciones' => json_encode($request->input('ubicaciones', [])),
                'observaciones_tecnicas' => $request->input('observaciones_tecnicas', ''),
                'observaciones_generales' => json_encode($request->input('observaciones_generales', [])),
                'especificaciones' => json_encode($request->input('especificaciones', [])),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Cotización actualizada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error al actualizar cotización de Prenda', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cotización'
            ], 500);
        }
    }

    /**
     * Enviar cotización de prenda
     */
    public function enviar(Request $request, $id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            $cotizacion->update([
                'es_borrador' => false,
                'estado' => 'ENVIADA'
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Cotización enviada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar la cotización'
            ], 500);
        }
    }

    /**
     * Eliminar cotización de prenda
     */
    public function destroy($id)
    {
        try {
            $cotizacion = Cotizacion::findOrFail($id);
            $cotizacion->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cotización'
            ], 500);
        }
    }
    
    /**
     * Procesar imágenes de la cotización
     */
    private function procesarImagenesCotizacion(Request $request, $cotizacionId)
    {
        // Implementar procesamiento de imágenes si es necesario
        Log::info('Procesando imágenes para cotización', ['cotizacion_id' => $cotizacionId]);
    }
}
