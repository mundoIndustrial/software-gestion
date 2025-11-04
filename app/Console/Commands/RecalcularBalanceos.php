<?php

namespace App\Console\Commands;

use App\Models\Balanceo;
use Illuminate\Console\Command;

class RecalcularBalanceos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balanceo:recalcular {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcula las métricas de uno o todos los balanceos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $balanceoId = $this->argument('id');

        if ($balanceoId) {
            // Recalcular un balanceo específico
            $balanceo = Balanceo::find($balanceoId);
            
            if (!$balanceo) {
                $this->error("Balanceo con ID {$balanceoId} no encontrado.");
                return 1;
            }

            $this->info("Recalculando balanceo ID: {$balanceoId}...");
            $balanceo->calcularMetricas();
            
            $this->info("✓ Balanceo recalculado correctamente");
            $this->line("  SAM Total: {$balanceo->sam_total}");
            $this->line("  Meta Teórica: {$balanceo->meta_teorica}");
            $this->line("  Meta Real: {$balanceo->meta_real}");
            $this->line("  Meta Sugerida 85%: {$balanceo->meta_sugerida_85}");
            
        } else {
            // Recalcular todos los balanceos
            $balanceos = Balanceo::all();
            $total = $balanceos->count();
            
            $this->info("Recalculando {$total} balanceos...");
            
            $bar = $this->output->createProgressBar($total);
            $bar->start();
            
            foreach ($balanceos as $balanceo) {
                $balanceo->calcularMetricas();
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            $this->info("✓ Todos los balanceos recalculados correctamente");
        }

        return 0;
    }
}
