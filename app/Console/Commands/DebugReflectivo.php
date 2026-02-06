<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidosProcesosPrendaDetalle;

class DebugReflectivo extends Command
{
    protected $signature = 'debug:reflectivo';
    protected $description = 'Debug REFLECTIVO receipts';

    public function handle()
    {
        $this->info('=== RECIBOS REFLECTIVO ENCONTRADOS ===');
        
        $recibosReflectivo = ConsecutivoReciboPedido::where('tipo_recibo', 'REFLECTIVO')
            ->where('activo', 1)
            ->with(['prenda', 'prenda.pedidoProduccion'])
            ->get();

        $this->info("Total: " . $recibosReflectivo->count());
        $this->newLine();

        foreach ($recibosReflectivo as $recibo) {
            $this->line("Recibo ID: {$recibo->id}");
            $this->line("Prenda ID: {$recibo->prenda_id}");
            $this->line("Pedido ID: {$recibo->pedido_produccion_id}");
            $this->line("Consecutivo: {$recibo->consecutivo_actual}");
            $this->line("Tipo: {$recibo->tipo_recibo}");
            
            if ($recibo->prenda) {
                $this->line("  Prenda: {$recibo->prenda->nombre_prenda}");
                
                if ($recibo->prenda->pedidoProduccion) {
                    $pedido = $recibo->prenda->pedidoProduccion;
                    $this->line("    Pedido #: {$pedido->numero_pedido}");
                    $this->line("    Estado: {$pedido->estado}");
                    $this->line("    Área: {$pedido->area}");
                }
            }
            
            // Verificar si está aprobado
            $detalleAprobado = PedidosProcesosPrendaDetalle::where('prenda_pedido_id', $recibo->prenda_id)
                ->where('estado', 'APROBADO')
                ->first();
            
            $this->line("  ✓ Aprobado: " . ($detalleAprobado ? "SÍ" : "NO"));
            $this->newLine();
        }

        // Verificar total de detalles APROBADOS
        $detallesAprobados = PedidosProcesosPrendaDetalle::where('estado', 'APROBADO')->count();
        $this->info("\n=== ESTADÍSTICAS ===");
        $this->line("Detalles APROBADOS en pedidos_procesos_prenda_detalles: {$detallesAprobados}");
    }
}
