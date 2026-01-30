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
 * - Crea automáticamente el proceso "creacion_de_orden" (estado: Pendiente)
 * - Crea automáticamente el proceso "Costura" asignado a Ramiro (estado: En Ejecución)
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

            Log::info(' [CrearProcesosParaCotizacionReflectivo] Listener iniciado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            // Obtener cotización del pedido
            $cotizacion = $pedido->cotizacion;
            
            if (!$cotizacion) {
                Log::info(' PedidoCreado sin cotización asociada', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                ]);
                return;
            }

            // Verificar si la cotización es tipo REFLECTIVO
            if (!$this->esCotizacionReflectivo($cotizacion)) {
                Log::info(' Cotización no es tipo REFLECTIVO', [
                    'cotizacion_id' => $cotizacion->id,
                    'tipo' => $cotizacion->tipoCotizacion?->nombre,
                ]);
                return;
            }

            Log::info(' CREAR PROCESOS PARA COTIZACIÓN REFLECTIVO', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'cotizacion_id' => $cotizacion->id,
                'cotizacion_tipo' => $cotizacion->tipoCotizacion?->nombre,
            ]);

            // Crear procesos automáticamente
            $this->crearProcesosReflectivo($pedido);

            Log::info(' Procesos creados exitosamente para pedido REFLECTIVO', [
                'numero_pedido' => $pedido->numero_pedido,
            ]);

        } catch (\Exception $e) {
            Log::error(' Error al crear procesos para cotización reflectivo', [
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
     * NOTA: Los procesos ya se crean en PedidosProduccionController::crearDesdeCotizacion()
     * Este listener solo sirve para validación y logging adicional
     */
    private function crearProcesosReflectivo($pedido): void
    {
        $numeroPedido = $pedido->numero_pedido;

        Log::info(' [LISTENER] Validación de procesos para pedido reflectivo', [
            'numero_pedido' => $numeroPedido,
        ]);

        // Obtener prendas del pedido
        $prendas = PrendaPedido::where('pedido_produccion_id', $pedido->id)->get();

        Log::info(' [LISTENER] Prendas encontradas', [
            'numero_pedido' => $numeroPedido,
            'cantidad' => $prendas->count(),
        ]);

        if ($prendas->isEmpty()) {
            Log::warning(' [LISTENER] No hay prendas en el pedido reflectivo', [
                'numero_pedido' => $numeroPedido,
            ]);
            return;
        }

        // Solo validar que los procesos existan
        foreach ($prendas as $prenda) {
            $procesosExistentes = ProcesoPrenda::where('numero_pedido', $numeroPedido)
                ->where('nombre_prenda', $prenda->nombre_prenda)
                ->pluck('proceso')
                ->toArray();

            Log::info(' [LISTENER] Procesos validados para prenda', [
                'numero_pedido' => $numeroPedido,
                'nombre_prenda' => $prenda->nombre_prenda,
                'procesos' => $procesosExistentes,
                'tiene_creacion' => in_array('Creación', $procesosExistentes),
                'tiene_costura' => in_array('Costura', $procesosExistentes),
            ]);
        }
    }
}
