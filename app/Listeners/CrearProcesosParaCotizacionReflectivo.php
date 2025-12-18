<?php

namespace App\Listeners;

use App\Events\PedidoCreado;
use App\Models\ProcesoPrenda;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

/**
 * Listener: CrearProcesosParaCotizacionReflectivo
 * 
 * Cuando se crea un pedido desde una cotización tipo REFLECTIVO:
 * - Crea automáticamente el proceso "creacion_de_orden"
 * - Crea automáticamente el proceso "costura" con encargado "Ramiro"
 * - El pedido salta la fase de INSUMOS y va directo a COSTURA
 */
class CrearProcesosParaCotizacionReflectivo
{

    /**
     * Handle the event.
     */
    public function handle(PedidoCreado $event): void
    {
        try {
            $pedido = $event->pedido;

            Log::info('🎯 [CrearProcesosParaCotizacionReflectivo] Listener iniciado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            // Obtener cotización del pedido
            $cotizacion = $pedido->cotizacion;
            
            if (!$cotizacion) {
                Log::info('📋 PedidoCreado sin cotización asociada', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                ]);
                return;
            }

            // Verificar si la cotización es tipo REFLECTIVO
            if (!$this->esCotizacionReflectivo($cotizacion)) {
                Log::info('📋 Cotización no es tipo REFLECTIVO', [
                    'cotizacion_id' => $cotizacion->id,
                    'tipo' => $cotizacion->tipoCotizacion?->nombre,
                ]);
                return;
            }

            Log::info('🎯 CREAR PROCESOS PARA COTIZACIÓN REFLECTIVO', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cotizacion_id' => $cotizacion->id,
                'cotizacion_tipo' => $cotizacion->tipoCotizacion?->nombre,
            ]);

            // Crear procesos automáticamente
            $this->crearProcesosReflectivo($pedido);

            Log::info('✅ Procesos creados exitosamente para pedido REFLECTIVO', [
                'numero_pedido' => $pedido->numero_pedido,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al crear procesos para cotización reflectivo', [
                'error' => $e->getMessage(),
                'pedido_id' => $event->pedido->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Verificar si la cotización es tipo REFLECTIVO
     */
    private function esCotizacionReflectivo($cotizacion): bool
    {
        if (!$cotizacion->tipoCotizacion) {
            return false;
        }

        $tipoCot = strtolower(trim($cotizacion->tipoCotizacion->nombre ?? ''));
        return $tipoCot === 'reflectivo';
    }

    /**
     * Crear procesos automáticamente para pedido REFLECTIVO
     * 
     * 1. Crea proceso "creacion_de_orden"
     * 2. Crea proceso "costura" con encargado "Ramiro"
     */
    private function crearProcesosReflectivo($pedido): void
    {
        $numeroPedido = $pedido->numero_pedido;

        Log::info('🔍 Buscando prendas para pedido reflectivo', [
            'numero_pedido' => $numeroPedido,
        ]);

        // Obtener prendas del pedido
        $prendas = PrendaPedido::where('numero_pedido', $numeroPedido)->get();

        Log::info('📋 Prendas encontradas', [
            'numero_pedido' => $numeroPedido,
            'cantidad' => $prendas->count(),
        ]);

        if ($prendas->isEmpty()) {
            Log::warn('⚠️ No hay prendas en el pedido reflectivo', [
                'numero_pedido' => $numeroPedido,
            ]);
            return;
        }

        foreach ($prendas as $prenda) {
            Log::info('➕ Creando procesos para prenda', [
                'numero_pedido' => $numeroPedido,
                'nombre_prenda' => $prenda->nombre_prenda,
            ]);

            try {
                // Proceso 1: Creación de Orden
                ProcesoPrenda::create([
                    'numero_pedido' => $numeroPedido,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'proceso' => 'creacion_de_orden',
                    'encargado' => null,
                    'estado_proceso' => 'Completado',
                    'fecha_inicio' => now(),
                    'fecha_final' => now(),
                    'observaciones' => 'Proceso automático para cotización reflectivo',
                ]);

                Log::info('✅ Proceso creacion_de_orden creado');

                // Proceso 2: Costura (con Ramiro como encargado)
                ProcesoPrenda::create([
                    'numero_pedido' => $numeroPedido,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'proceso' => 'Costura',
                    'encargado' => 'Ramiro',
                    'estado_proceso' => 'En Ejecución',
                    'fecha_inicio' => now(),
                    'observaciones' => 'Asignado automáticamente a Ramiro para cotización reflectivo',
                ]);

                Log::info('✅ Proceso Costura creado con Ramiro', [
                    'numero_pedido' => $numeroPedido,
                    'nombre_prenda' => $prenda->nombre_prenda,
                ]);

            } catch (\Exception $e) {
                Log::error('❌ Error creando procesos para prenda', [
                    'error' => $e->getMessage(),
                    'numero_pedido' => $numeroPedido,
                    'nombre_prenda' => $prenda->nombre_prenda,
                ]);
            }
        }
    }
}
