<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\NumeroSecuencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
     * SINCRÓNICO: Genera número INMEDIATAMENTE con pessimistic lock
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

                // Generar número SINCRONICAMENTE si se envía
                $numeroCotizacion = null;
                if (!$esBorrador) {
                    $numeroCotizacion = $this->generarNumeroCotizacion('cotizaciones_prenda');
                    Log::info('✅ Número generado sincronicamente', [
                        'numero' => $numeroCotizacion
                    ]);
                }

                // Crear cotización CON número generado
                $cotizacion = Cotizacion::create([
                    'asesor_id' => Auth::id(),
                    'cliente_id' => $clienteId,
                    'numero_cotizacion' => $numeroCotizacion,
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
                    'numero_cotizacion' => $numeroCotizacion,
                    'es_borrador' => $esBorrador,
                    'estado' => $estado,
                    'cliente_id' => $clienteId,
                ]);

                // OPTIMIZACIÓN: Si se envía, aún encolamos el job pero ahora el número YA EXISTE
                // El job puede usarlo directamente sin generar otro
                if (!$esBorrador) {
                    \App\Jobs\ProcesarEnvioCotizacionJob::dispatch(
                        $cotizacion->id,
                        3 // tipo_cotizacion_id para Prenda
                    )->onQueue('cotizaciones');

                    Log::info('📋 Job de envío encolado (número ya existe)', [
                        'cotizacion_id' => $cotizacion->id,
                        'numero' => $numeroCotizacion,
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
                    'message' => $esBorrador ? 'Cotización guardada como borrador' : 'Cotización enviada - Número: ' . $numeroCotizacion,
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
     * Generar número de cotización sincronicamente con pessimistic lock
     * 
     * Usa lockForUpdate() para prevenir race conditions
     * Formato: COT-20250124-001
     * 
     * @param string $tipo tipo de secuencia (cotizaciones_prenda, cotizaciones_bordado, etc)
     * @return string número generado
     */
    private function generarNumeroCotizacion($tipo = 'cotizaciones_prenda')
    {
        // Usar secuencia universal para TODAS las cotizaciones
        $secuencia = DB::table('numero_secuencias')
            ->lockForUpdate()
            ->where('tipo', 'cotizaciones_universal')
            ->first();

        if (!$secuencia) {
            throw new \Exception("Secuencia universal 'cotizaciones_universal' no encontrada en numero_secuencias");
        }

        // Obtener próximo número
        $siguiente = $secuencia->siguiente;

        // Incrementar y guardar
        DB::table('numero_secuencias')
            ->where('tipo', 'cotizaciones_universal')
            ->update(['siguiente' => $siguiente + 1]);

        // Generar formato: COT-000001
        $numero = 'COT-' . str_pad($siguiente, 6, '0', STR_PAD_LEFT);

        Log::debug('🔐 Número generado con lock universal', [
            'tipo_recibido' => $tipo,
            'numero' => $numero,
            'secuencia_anterior' => $siguiente,
            'secuencia_nueva' => $siguiente + 1
        ]);

        return $numero;
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
        $cotizacion = Cotizacion::with([
            'cliente',
            'prendas.fotos',
            'prendas.telaFotos',
            'prendas.tallas',
            'prendas.variantes.genero',
            'prendas.variantes.manga',
            'prendas.variantes.broche',
            'logoCotizacion.fotos'
        ])->findOrFail($id);
        
        // Verificar que el usuario es propietario
        if ($cotizacion->asesor_id !== Auth::id()) {
            abort(403, 'No tienes permiso para editar esta cotización');
        }
        
        Log::info('CotizacionPrendaController@edit: Cotización cargada para editar', [
            'cotizacion_id' => $cotizacion->id,
            'prendas_count' => $cotizacion->prendas ? count($cotizacion->prendas) : 0,
            'es_borrador' => $cotizacion->es_borrador,
        ]);
        
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
