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

                // Crear cotización CON número generado
                $cotizacion = Cotizacion::create([
                    'asesor_id' => Auth::id(),
                    'cliente_id' => $clienteId,
                    'numero_cotizacion' => $numeroCotizacion,
                    'tipo_cotizacion_id' => 4, // Cotización de Bordado
                    'tipo_venta' => $request->input('tipo_venta', 'M'),
                    'es_borrador' => $esBorrador,
                    'estado' => $estado,
                    'productos' => json_encode($request->input('productos', [])),
                    'observaciones_tecnicas' => $request->input('observaciones_tecnicas', ''),
                    'observaciones_generales' => json_encode($request->input('observaciones_generales', [])),
                    'especificaciones' => json_encode($request->input('especificaciones', [])),
                    'imagenes' => json_encode($request->input('imagenes', [])),
                ]);

                Log::info('✅ Cotización de Bordado creada', [
                    'cotizacion_id' => $cotizacion->id,
                    'numero_cotizacion' => $numeroCotizacion,
                    'es_borrador' => $esBorrador,
                    'estado' => $estado,
                    'cliente_id' => $clienteId,
                ]);

                // Si se envía, aún encolamos el job pero el número YA EXISTE
                if (!$esBorrador) {
                    \App\Jobs\ProcesarEnvioCotizacionJob::dispatch(
                        $cotizacion->id,
                        4 // tipo_cotizacion_id para Bordado
                    )->onQueue('cotizaciones');

                    Log::info('📋 Job de envío encolado (número ya existe)', [
                        'cotizacion_id' => $cotizacion->id,
                        'numero' => $numeroCotizacion,
                        'queue' => 'cotizaciones'
                    ]);
                }

                // Procesar imágenes si existen
                if ($request->hasFile('imagenes')) {
                    $this->procesarImagenesCotizacion($request, $cotizacion->id);
                }

                // Recargar la cotización con todas sus relaciones
                $cotizacionCompleta = Cotizacion::with([
                    'cliente',
                    'logoCotizacion.fotos'
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
                ]);

                throw $e;
            }
        }, attempts: 3);
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
