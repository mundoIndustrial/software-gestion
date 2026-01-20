<?php

namespace App\Domain\PedidoProduccion\Services;

use App\Models\PedidoProduccion;
use App\Models\Cotizacion;
use App\Models\ProcesoPrenda;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de dominio para gestión de procesos de pedidos
 * Responsabilidad: Crear y gestionar procesos asociados a pedidos
 */
class ProcesosPedidoService
{
    /**
     * Crear procesos para pedido reflectivo
     */
    public function crearProcesosReflectivo(PedidoProduccion $pedido, Cotizacion $cotizacion): void
    {
        try {
            $asesoraLogueada = Auth::user()->name;

            foreach ($pedido->prendas as $prenda) {
                // Obtener procesos existentes
                $procesosExistentes = $prenda->procesos->pluck('proceso')->toArray();

                \Log::info(' Creando procesos para prenda reflectivo', [
                    'numero_pedido' => $pedido->numero_pedido,
                    'prenda_pedido_id' => $prenda->id,
                    'nombre_prenda' => $prenda->nombre_prenda,
                    'procesos' => $procesosExistentes,
                ]);

                // Crear proceso de Creación de Orden
                if (!in_array('Creación de Orden', $procesosExistentes)) {
                    $procsCreacion = ProcesoPrenda::create([
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_pedido_id' => $prenda->id,
                        'proceso' => 'Creación de Orden',
                        'encargado' => $asesoraLogueada,
                        'estado_proceso' => 'En Progreso',
                        'fecha_inicio' => now(),
                        'observaciones' => 'Proceso de creación asignado automáticamente a la asesora para cotización reflectivo',
                    ]);

                    \Log::info(' Proceso Creación de Orden creado', [
                        'proceso_id' => $procsCreacion->id,
                        'encargado' => $asesoraLogueada,
                    ]);
                }

                // Crear proceso Costura
                if (!in_array('Costura', $procesosExistentes)) {
                    $procsCostura = ProcesoPrenda::create([
                        'numero_pedido' => $pedido->numero_pedido,
                        'prenda_pedido_id' => $prenda->id,
                        'proceso' => 'Costura',
                        'encargado' => 'Ramiro',
                        'estado_proceso' => 'En Progreso',
                        'fecha_inicio' => now(),
                        'observaciones' => 'Asignado automáticamente a Ramiro para cotización reflectivo',
                    ]);

                    \Log::info(' Proceso Costura creado', [
                        'proceso_id' => $procsCostura->id,
                        'encargado' => 'Ramiro',
                    ]);
                }
            }

            \Log::info(' Procesos de cotización reflectivo completados', [
                'numero_pedido' => $pedido->numero_pedido,
            ]);

        } catch (\Exception $e) {
            \Log::error(' Error al crear procesos para cotización reflectivo', [
                'error' => $e->getMessage(),
                'numero_pedido' => $pedido->numero_pedido ?? 'N/A',
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Crear proceso inicial para prenda
     */
    public function crearProcesoInicial(int $numeroPedido, int $prendaPedidoId, string $proceso = 'Creación de Orden'): void
    {
        ProcesoPrenda::create([
            'numero_pedido' => $numeroPedido,
            'prenda_pedido_id' => $prendaPedidoId,
            'proceso' => $proceso,
            'encargado' => Auth::user()->name,
            'estado_proceso' => 'Pendiente',
            'fecha_inicio' => now(),
            'fecha_fin' => now(),
        ]);
    }
}
