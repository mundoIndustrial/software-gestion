<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;

class RevisarEstadosCotizaciones extends Command
{
    protected $signature = 'revisar:cotizaciones';
    protected $description = 'Revisa los estados de cotizaciones en BD';

    public function handle()
    {
        $this->info('=== REVISANDO COTIZACIONES EN BD ===\n');

        $todas = Cotizacion::all();
        $this->info("Total de cotizaciones: " . $todas->count() . "\n");

        // Agrupar por estado
        $porEstado = $todas->groupBy('estado');
        $this->info("RESUMEN POR ESTADO:");
        foreach ($porEstado as $estado => $cotizaciones) {
            $this->line("  - $estado: " . count($cotizaciones) . " cotizaciones");
        }

        $this->info("\n=== DETALLES DE COTIZACIONES ===");
        foreach ($todas as $cot) {
            $cliente = $cot->cliente ? $cot->cliente->nombre : 'SIN CLIENTE';
            $this->line("ID: {$cot->id} | NUM: {$cot->numero_cotizacion} | ESTADO: {$cot->estado} | CLIENTE: $cliente");
        }

        $this->info("\n=== COTIZACIONES QUE SE MOSTRARÍAN (APROBADAS) ===");
        $aprobadas = Cotizacion::whereIn('estado', ['APROBADA_COTIZACIONES', 'APROBADO_PARA_PEDIDO'])->get();
        $this->info("Total que se mostrarían: " . $aprobadas->count());
        
        if ($aprobadas->count() === 0) {
            $this->warn("\n⚠️ NO HAY COTIZACIONES APROBADAS");
            $this->line("\n¿Deseas actualizar 5 cotizaciones al estado APROBADA_COTIZACIONES?");
            
            if ($this->confirm('¿Actualizar?')) {
                $updatedCount = Cotizacion::limit(5)->update(['estado' => 'APROBADA_COTIZACIONES']);
                $this->info("✅ Se actualizaron $updatedCount cotizaciones a APROBADA_COTIZACIONES");
            }
        } else {
            foreach ($aprobadas as $cot) {
                $cliente = $cot->cliente ? $cot->cliente->nombre : 'SIN CLIENTE';
                $this->line("✅ ID: {$cot->id} | NUM: {$cot->numero_cotizacion} | CLIENTE: $cliente");
            }
        }
    }
}
