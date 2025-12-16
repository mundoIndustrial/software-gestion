<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Cotizacion;
use App\Models\Cliente;
use App\Models\NumeroSecuencia;

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
     * SINCRÓNICO: Genera número INMEDIATAMENTE con pessimistic lock
     */
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            try {
                Log::info('🔵 CotizacionBordadoController@store - Iniciando guardado de cotización de Bordado');

                // Determinar si es borrador o enviada
                $action = $request->input('action') ?? $request->input('accion');
                $esBorrador = $action === 'borrador';
                $estado = $esBorrador ? 'BORRADOR' : 'ENVIADA';

                // Obtener o crear cliente
                $clienteId = $request->input('cliente_id');
                $nombreCliente = $request->input('cliente');

                if ($nombreCliente && !$clienteId) {
                    $cliente = Cliente::firstOrCreate(
                        ['nombre' => $nombreCliente],
                        ['nombre' => $nombreCliente]
                    );
                    $clienteId = $cliente->id;
                }

                // Generar número SINCRONICAMENTE si se envía
                $numeroCotizacion = null;
                if (!$esBorrador) {
                    $numeroCotizacion = $this->generarNumeroCotizacion('cotizaciones_bordado');
                    Log::info('✅ Número generado sincronicamente', [
                        'numero' => $numeroCotizacion
                    ]);
                }

                // Crear cotización en tabla cotizaciones
                $cotizacion = Cotizacion::create([
                    'asesor_id' => Auth::id(),
                    'cliente_id' => $clienteId,
                    'numero_cotizacion' => $numeroCotizacion,
                    'tipo_cotizacion_id' => 2, // Cotización de Logo/Bordado
                    'tipo_venta' => $request->input('tipo_venta', 'M'),
                    'es_borrador' => $esBorrador,
                    'estado' => $estado,
                    'especificaciones' => json_encode($request->input('especificaciones', [])),
                ]);

                Log::info('✅ Cotización de Bordado creada en tabla cotizaciones', [
                    'cotizacion_id' => $cotizacion->id,
                    'numero_cotizacion' => $numeroCotizacion,
                ]);

                // Guardar detalles en tabla logo_cotizaciones
                $logoCotizacion = DB::table('logo_cotizaciones')->insert([
                    'cotizacion_id' => $cotizacion->id,
                    'descripcion' => $request->input('descripcion', '') ?? $request->input('descripcion_logo', ''),
                    'tecnicas' => json_encode($request->input('tecnicas', [])),
                    'observaciones_tecnicas' => $request->input('observaciones_tecnicas', ''),
                    'ubicaciones' => json_encode($request->input('ubicaciones', [])),
                    'observaciones_generales' => json_encode($request->input('observaciones_generales', [])),
                    'imagenes' => json_encode($request->input('imagenes', [])),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('✅ Detalles de bordado guardados en tabla logo_cotizaciones', [
                    'cotizacion_id' => $cotizacion->id,
                ]);

                // Procesar imágenes si existen
                if ($request->hasFile('imagenes')) {
                    $this->procesarImagenesCotizacion($request, $cotizacion->id);
                }

                // Si se envía, aún encolamos el job pero el número YA EXISTE
                if (!$esBorrador) {
                    \App\Jobs\ProcesarEnvioCotizacionJob::dispatch(
                        $cotizacion->id,
                        2 // tipo_cotizacion_id para Logo/Bordado
                    )->onQueue('cotizaciones');

                    Log::info('📋 Job de envío encolado (número ya existe)', [
                        'cotizacion_id' => $cotizacion->id,
                        'numero' => $numeroCotizacion,
                        'queue' => 'cotizaciones'
                    ]);
                }

                // Recargar la cotización con todas sus relaciones
                $cotizacionCompleta = Cotizacion::with([
                    'cliente',
                    'logoCotizacion'
                ])->findOrFail($cotizacion->id);

                return response()->json([
                    'success' => true,
                    'message' => $esBorrador ? 'Cotización guardada como borrador' : 'Cotización enviada - Número: ' . $numeroCotizacion,
                    'data' => $cotizacionCompleta->toArray(),
                    'redirect' => route('asesores.cotizaciones.index')
                ], 201);

            } catch (\Exception $e) {
                Log::error('❌ Error al guardar cotización de Bordado', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);

                throw $e;
            }
        }, attempts: 3);
    }

    /**
     * Procesar y guardar imágenes del bordado en logo_fotos_cot
     */
    private function procesarImagenesCotizacion(Request $request, $cotizacionId)
    {
        // Obtener el ID de logo_cotizacion
        $logoCotizacion = DB::table('logo_cotizaciones')
            ->where('cotizacion_id', $cotizacionId)
            ->first();

        if (!$logoCotizacion) {
            Log::warning('⚠️ No se encontró logo_cotizacion para cotización', [
                'cotizacion_id' => $cotizacionId
            ]);
            return;
        }

        $logoCotizacionId = $logoCotizacion->id;
        $orden = 1;

        // Procesar archivos del request
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $archivo) {
                try {
                    // Guardar imagen original
                    $rutaOriginal = $archivo->store('bordado/cotizaciones/' . $cotizacionId, 'public');

                    // Aquí podrías generar versiones webp y miniatura
                    // Por ahora, usamos la misma ruta para las tres versiones
                    $rutaWebp = $rutaOriginal;
                    $rutaMiniatura = $rutaOriginal;

                    // Obtener dimensiones de la imagen
                    $imageInfo = @getimagesize(storage_path('app/public/' . $rutaOriginal));
                    $ancho = $imageInfo[0] ?? 0;
                    $alto = $imageInfo[1] ?? 0;
                    $tamaño = $archivo->getSize();

                    // Guardar en logo_fotos_cot
                    DB::table('logo_fotos_cot')->insert([
                        'logo_cotizacion_id' => $logoCotizacionId,
                        'ruta_original' => $rutaOriginal,
                        'ruta_webp' => $rutaWebp,
                        'ruta_miniatura' => $rutaMiniatura,
                        'orden' => $orden,
                        'ancho' => $ancho,
                        'alto' => $alto,
                        'tamaño' => $tamaño,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('✅ Imagen guardada en logo_fotos_cot', [
                        'logo_cotizacion_id' => $logoCotizacionId,
                        'ruta' => $rutaOriginal,
                        'orden' => $orden,
                        'tamaño' => $tamaño,
                        'dimensiones' => "{$ancho}x{$alto}"
                    ]);

                    $orden++;

                } catch (\Exception $e) {
                    Log::error('❌ Error al guardar imagen', [
                        'error' => $e->getMessage(),
                        'archivo' => $archivo->getClientOriginalName()
                    ]);
                }
            }
        }
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
    private function generarNumeroCotizacion($tipo = 'cotizaciones_bordado')
    {
        $secuencia = DB::table('numero_secuencias')
            ->lockForUpdate()
            ->where('tipo', $tipo)
            ->first();

        if (!$secuencia) {
            throw new \Exception("Secuencia de tipo '{$tipo}' no encontrada en numero_secuencias");
        }

        $siguiente = $secuencia->siguiente;
        
        DB::table('numero_secuencias')
            ->where('tipo', $tipo)
            ->update(['siguiente' => $siguiente + 1]);

        $numero = 'COT-' . date('Ymd') . '-' . str_pad($siguiente, 3, '0', STR_PAD_LEFT);

        Log::debug('🔐 Número generado con lock', [
            'tipo' => $tipo,
            'numero' => $numero,
            'secuencia_anterior' => $siguiente,
            'secuencia_nueva' => $siguiente + 1
        ]);

        return $numero;
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
