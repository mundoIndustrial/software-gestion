<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TablaOriginal;
use App\Models\Festivo;
use Carbon\Carbon;

class TestFechaEstimada extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:fecha-estimada';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prueba el cálculo de fecha estimada de entrega';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== PRUEBA DE CÁLCULO DE FECHA ESTIMADA ===');
        $this->newLine();

        // Obtener festivos
        $festivos = Festivo::pluck('fecha')->toArray();

        // Obtener órdenes con dia_de_entrega definido
        $ordenes = TablaOriginal::whereNotNull('dia_de_entrega')
            ->where('dia_de_entrega', '>', 0)
            ->limit(5)
            ->get();

        if ($ordenes->isEmpty()) {
            $this->warn('⚠️ No hay órdenes con días de entrega definidos');
            return;
        }

        foreach ($ordenes as $orden) {
            $this->line('─────────────────────────────────────');
            $this->info("Pedido: {$orden->pedido}");
            $this->line("Cliente: {$orden->cliente}");
            
            if ($orden->fecha_de_creacion_de_orden) {
                $this->line("Fecha de Creación: " . Carbon::parse($orden->fecha_de_creacion_de_orden)->format('d/m/Y'));
            }
            
            $this->line("Días de Entrega: {$orden->dia_de_entrega}");

            // Establecer festivos en el modelo
            $orden->setFestivos($festivos);

            // Calcular fecha estimada
            $fechaEstimada = $orden->calcularFechaEstimadaEntrega();

            if ($fechaEstimada) {
                $this->line("Fecha Estimada: " . $fechaEstimada->format('d/m/Y'));
                $this->line("Accessor: " . $orden->getFechaEstimadaEntregaFormattedAttribute());
                $this->info("✅ Cálculo exitoso");
            } else {
                $this->warn("⚠️ No se pudo calcular (faltan datos)");
            }
        }

        $this->newLine();
        $this->info('✅ Prueba completada');
    }
}
