<?php

namespace App\Services\Insumos;

use App\Models\ProcesosPrenda;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\PedidosProcesosPrendaDetalle;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para crear procesos automáticamente cuando se aprueba un pedido
 */
class ProcesoAutomaticoService
{
    /**
     * Procesos disponibles según el procesoIconMap
     */
    protected const PROCESOS_DISPONIBLES = [
        'Pedido Recibido' => 'description',
        'Creación Orden' => 'description',
        'Insumos' => 'inventory_2',
        'Insumos y Telas' => 'inventory_2',
        'Corte' => 'content_cut',
        'Bordado' => 'brush',
        'Estampado' => 'print',
        'Costura' => 'dry_cleaning',
        'Polos' => 'checkroom',
        'Taller' => 'construction',
        'Lavandería' => 'local_laundry_service',
        'Lavanderia' => 'local_laundry_service',
        'Arreglos' => 'handyman',
        'Control de Calidad' => 'verified',
        'Control-Calidad' => 'verified',
        'Entrega' => 'local_shipping',
        'Despacho' => 'directions_car',
        'Despachos' => 'directions_car',
        'Reflectivo' => 'highlight',
        'Marras' => 'search'
    ];

    /**
     * Crea únicamente el proceso de Corte para un pedido cuando se aprueba
     */
    public function crearProcesosParaPedido($numeroPedido)
    {
        try {
            Log::info("Iniciando creación de proceso de Corte para pedido #{$numeroPedido}");

            // Obtener el pedido
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            if (!$pedido) {
                throw new \Exception("Pedido #{$numeroPedido} no encontrado");
            }

            // Crear únicamente el proceso de Corte
            $this->crearProcesoBase($numeroPedido, 'Corte', 'Pendiente');
            $procesosCreados = 1;
            $procesosDetalles = ['Corte'];

            Log::info("Proceso de Corte creado exitosamente para pedido #{$numeroPedido}", [
                'total_procesos' => $procesosCreados,
                'detalles' => $procesosDetalles
            ]);

            return [
                'success' => true,
                'message' => "Proceso de Corte creado correctamente",
                'procesos_creados' => $procesosCreados,
                'detalles' => $procesosDetalles
            ];

        } catch (\Exception $e) {
            Log::error("Error al crear proceso de Corte para pedido #{$numeroPedido}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al crear proceso de Corte: ' . $e->getMessage(),
                'procesos_creados' => 0
            ];
        }
    }

    /**
     * Determina los procesos necesarios para una prenda específica
     */
    protected function determinarProcesosPorPrenda(PrendaPedido $prenda)
    {
        $procesos = [];

        // Procesos base para todas las prendas
        $procesos[] = 'Costura';

        // Analizar procesos especiales desde la tabla de procesos de prendas
        $procesosEspeciales = PedidosProcesosPrendaDetalle::where('prenda_pedido_id', $prenda->id)->get();
        
        foreach ($procesosEspeciales as $procesoEspecial) {
            $nombreProceso = $this->normalizarNombreProceso($procesoEspecial->proceso);
            
            if ($nombreProceso && !in_array($nombreProceso, $procesos)) {
                $procesos[] = $nombreProceso;
            }
        }

        // Analizar variantes para detectar procesos adicionales
        if ($prenda->variantes) {
            foreach ($prenda->variantes as $variante) {
                // Detectar reflectivo
                if ($variante->tiene_reflectivo) {
                    if (!in_array('Reflectivo', $procesos)) {
                        $procesos[] = 'Reflectivo';
                    }
                }
                
                // Detectar bordado (desde descripción adicional)
                if ($variante->descripcion_adicional && strpos(strtolower($variante->descripcion_adicional), 'bordado') !== false) {
                    if (!in_array('Bordado', $procesos)) {
                        $procesos[] = 'Bordado';
                    }
                }
                
                // Detectar estampado (desde descripción adicional)
                if ($variante->descripcion_adicional && strpos(strtolower($variante->descripcion_adicional), 'estampado') !== false) {
                    if (!in_array('Estampado', $procesos)) {
                        $procesos[] = 'Estampado';
                    }
                }
            }
        }

        return $procesos;
    }

    /**
     * Normaliza el nombre del proceso según los disponibles
     */
    protected function normalizarNombreProceso($nombreProceso)
    {
        $nombreNormalizado = trim($nombreProceso);
        
        // Verificar si el proceso está en la lista de disponibles
        if (isset(self::PROCESOS_DISPONIBLES[$nombreNormalizado])) {
            return $nombreNormalizado;
        }

        // Intentar coincidencias parciales
        foreach (self::PROCESOS_DISPONIBLES as $procesoValido => $icon) {
            if (stripos($nombreNormalizado, $procesoValido) !== false || stripos($procesoValido, $nombreNormalizado) !== false) {
                return $procesoValido;
            }
        }

        // Si no coincide, retornar el original si parece válido
        if (strlen($nombreNormalizado) > 2) {
            return $nombreNormalizado;
        }

        return null;
    }

    /**
     * Crea un proceso base para el pedido (sin asociar a prenda específica)
     */
    protected function crearProcesoBase($numeroPedido, $nombreProceso, $estado = 'Pendiente')
    {
        return ProcesosPrenda::create([
            'numero_pedido' => $numeroPedido,
            'proceso' => $nombreProceso,
            'fecha_inicio' => now(),
            'estado_proceso' => $estado,
            'observaciones' => "Proceso creado automáticamente al aprobar pedido",
            'codigo_referencia' => $this->generarCodigoReferencia($numeroPedido, $nombreProceso),
        ]);
    }

    /**
     * Crea un proceso para una prenda específica
     */
    protected function crearProcesoParaPrenda($numeroPedido, $prendaPedidoId, $nombreProceso)
    {
        return ProcesosPrenda::create([
            'numero_pedido' => $numeroPedido,
            'prenda_pedido_id' => $prendaPedidoId,
            'proceso' => $nombreProceso,
            'fecha_inicio' => now(),
            'estado_proceso' => 'Pendiente',
            'observaciones' => "Proceso creado automáticamente para prenda ID: {$prendaPedidoId}",
            'codigo_referencia' => $this->generarCodigoReferencia($numeroPedido, $nombreProceso, $prendaPedidoId),
        ]);
    }

    /**
     * Verifica si ya existe un proceso para el pedido
     */
    protected function existeProceso($numeroPedido, $nombreProceso)
    {
        return ProcesosPrenda::where('numero_pedido', $numeroPedido)
            ->where('proceso', $nombreProceso)
            ->exists();
    }

    /**
     * Genera un código de referencia único
     */
    protected function generarCodigoReferencia($numeroPedido, $nombreProceso, $prendaPedidoId = null)
    {
        $base = "P{$numeroPedido}-" . strtoupper(substr($nombreProceso, 0, 3));
        if ($prendaPedidoId) {
            $base .= "-PP{$prendaPedidoId}";
        }
        
        // Agregar timestamp corto para unicidad
        $base .= "-" . date('His');
        
        return $base;
    }

    /**
     * Obtiene la lista de procesos disponibles
     */
    public static function getProcesosDisponibles()
    {
        return array_keys(self::PROCESOS_DISPONIBLES);
    }

    /**
     * Verifica si un nombre de proceso es válido
     */
    public static function esProcesoValido($nombreProceso)
    {
        return isset(self::PROCESOS_DISPONIBLES[$nombreProceso]);
    }
}
