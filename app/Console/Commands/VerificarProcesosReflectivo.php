<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PedidoProduccion;
use App\Models\ProcesoPrenda;

class VerificarProcesosReflectivo extends Command
{
    protected $signature = 'verificar:procesos-reflectivo';
    protected $description = 'Verificar procesos creados para pedidos reflectivos';

    public function handle()
    {
        $this->info(' VERIFICAR PROCESOS REFLECTIVOS');
        $this->line(str_repeat('=', 60));

        // Obtener últimos 5 pedidos
        $pedidos = PedidoProduccion::latest()->take(5)->get();

        foreach ($pedidos as $pedido) {
            $this->line("\n Pedido: {$pedido->numero_pedido}");
            $this->line("   ID: {$pedido->id}");
            $this->line("   Cotización: {$pedido->numero_cotizacion}");
            
            if ($pedido->cotizacion) {
                $this->line("   Tipo Cotización: " . ($pedido->cotizacion->tipoCotizacion?->nombre ?? 'N/A'));
            } else {
                $this->line("    SIN COTIZACIÓN");
            }

            // Obtener procesos
            $procesos = ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                ->distinct('proceso')
                ->get(['proceso', 'encargado', 'estado_proceso']);

            $this->line("   Procesos: " . ($procesos->count() > 0 ? $procesos->count() : ' NINGUNO'));
            
            foreach ($procesos as $proceso) {
                $encargado = $proceso->encargado ? " ✓ {$proceso->encargado}" : " (Sin asignar)";
                $this->line("      - {$proceso->proceso}:{$encargado}");
            }
        }

        $this->line("\n Verificación completada");
    }
}
