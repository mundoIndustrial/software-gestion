<?php

namespace App\Application\Insumos\Services;

use App\Models\PedidoProduccion;
use App\Models\PedidosProcesosPrendaDetalle;
use App\Models\ProcesosPrenda;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

class ProcesoAutomaticoService
{
    protected const PROCESOS_DISPONIBLES = [
        'Pedido Recibido' => 'description',
        'Creacion Orden' => 'description',
        'Insumos' => 'inventory_2',
        'Insumos y Telas' => 'inventory_2',
        'Corte' => 'content_cut',
        'Bordado' => 'brush',
        'Estampado' => 'print',
        'Costura' => 'dry_cleaning',
        'Polos' => 'checkroom',
        'Taller' => 'construction',
        'Lavanderia' => 'local_laundry_service',
        'Arreglos' => 'handyman',
        'Control de Calidad' => 'verified',
        'Control-Calidad' => 'verified',
        'Entrega' => 'local_shipping',
        'Despacho' => 'directions_car',
        'Despachos' => 'directions_car',
        'Reflectivo' => 'highlight',
        'Marras' => 'search',
    ];

    public function crearProcesosParaPedido($numeroPedido): array
    {
        try {
            Log::info("Iniciando creacion de proceso de Corte para pedido #{$numeroPedido}");

            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            if (!$pedido) {
                throw new \Exception("Pedido #{$numeroPedido} no encontrado");
            }

            $this->crearProcesoBase($numeroPedido, 'Corte', 'Pendiente');
            $procesosCreados = 1;
            $procesosDetalles = ['Corte'];

            Log::info("Proceso de Corte creado exitosamente para pedido #{$numeroPedido}", [
                'total_procesos' => $procesosCreados,
                'detalles' => $procesosDetalles,
            ]);

            return [
                'success' => true,
                'message' => 'Proceso de Corte creado correctamente',
                'procesos_creados' => $procesosCreados,
                'detalles' => $procesosDetalles,
            ];
        } catch (\Exception $e) {
            Log::error("Error al crear proceso de Corte para pedido #{$numeroPedido}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al crear proceso de Corte: ' . $e->getMessage(),
                'procesos_creados' => 0,
            ];
        }
    }

    protected function determinarProcesosPorPrenda(PrendaPedido $prenda): array
    {
        $procesos = ['Costura'];

        $procesosEspeciales = PedidosProcesosPrendaDetalle::where('prenda_pedido_id', $prenda->id)->get();
        foreach ($procesosEspeciales as $procesoEspecial) {
            $nombreProceso = $this->normalizarNombreProceso($procesoEspecial->proceso);
            if ($nombreProceso && !in_array($nombreProceso, $procesos, true)) {
                $procesos[] = $nombreProceso;
            }
        }

        if ($prenda->variantes) {
            foreach ($prenda->variantes as $variante) {
                if ($variante->tiene_reflectivo && !in_array('Reflectivo', $procesos, true)) {
                    $procesos[] = 'Reflectivo';
                }
                if (
                    $variante->descripcion_adicional &&
                    strpos(strtolower($variante->descripcion_adicional), 'bordado') !== false &&
                    !in_array('Bordado', $procesos, true)
                ) {
                    $procesos[] = 'Bordado';
                }
                if (
                    $variante->descripcion_adicional &&
                    strpos(strtolower($variante->descripcion_adicional), 'estampado') !== false &&
                    !in_array('Estampado', $procesos, true)
                ) {
                    $procesos[] = 'Estampado';
                }
            }
        }

        return $procesos;
    }

    protected function normalizarNombreProceso($nombreProceso): ?string
    {
        $nombreNormalizado = trim((string) $nombreProceso);

        if (isset(self::PROCESOS_DISPONIBLES[$nombreNormalizado])) {
            return $nombreNormalizado;
        }

        foreach (self::PROCESOS_DISPONIBLES as $procesoValido => $icon) {
            if (stripos($nombreNormalizado, $procesoValido) !== false || stripos($procesoValido, $nombreNormalizado) !== false) {
                return $procesoValido;
            }
        }

        if (strlen($nombreNormalizado) > 2) {
            return $nombreNormalizado;
        }

        return null;
    }

    protected function crearProcesoBase($numeroPedido, $nombreProceso, $estado = 'Pendiente')
    {
        return ProcesosPrenda::create([
            'numero_pedido' => $numeroPedido,
            'proceso' => $nombreProceso,
            'fecha_inicio' => now(),
            'estado_proceso' => $estado,
            'observaciones' => 'Proceso creado automaticamente al aprobar pedido',
            'codigo_referencia' => $this->generarCodigoReferencia($numeroPedido, $nombreProceso),
        ]);
    }

    protected function crearProcesoParaPrenda($numeroPedido, $prendaPedidoId, $nombreProceso)
    {
        return ProcesosPrenda::create([
            'numero_pedido' => $numeroPedido,
            'prenda_pedido_id' => $prendaPedidoId,
            'proceso' => $nombreProceso,
            'fecha_inicio' => now(),
            'estado_proceso' => 'Pendiente',
            'observaciones' => "Proceso creado automaticamente para prenda ID: {$prendaPedidoId}",
            'codigo_referencia' => $this->generarCodigoReferencia($numeroPedido, $nombreProceso, $prendaPedidoId),
        ]);
    }

    protected function existeProceso($numeroPedido, $nombreProceso): bool
    {
        return ProcesosPrenda::where('numero_pedido', $numeroPedido)
            ->where('proceso', $nombreProceso)
            ->exists();
    }

    protected function generarCodigoReferencia($numeroPedido, $nombreProceso, $prendaPedidoId = null): string
    {
        $base = "P{$numeroPedido}-" . strtoupper(substr((string) $nombreProceso, 0, 3));
        if ($prendaPedidoId) {
            $base .= "-PP{$prendaPedidoId}";
        }

        $base .= '-' . date('His');

        return $base;
    }

    public static function getProcesosDisponibles(): array
    {
        return array_keys(self::PROCESOS_DISPONIBLES);
    }

    public static function esProcesoValido($nombreProceso): bool
    {
        return isset(self::PROCESOS_DISPONIBLES[$nombreProceso]);
    }
}

