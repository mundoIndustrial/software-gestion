<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProcesoPrenda;
use App\Services\CalculadorDiasService;

class CalcularDiasProcesos extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'procesos:calcular-dias {--dry-run} {--fix-all}';

    /**
     * The console command description.
     */
    protected $description = 'Calcula y actualiza los días de duración en procesos_prenda. Use --fix-all para procesar todos.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $fixAll = $this->option('fix-all');

        $this->info('╔════════════════════════════════════════════════╗');
        $this->info('║  Cálculo de Días en Procesos                  ║');
        $this->info('╚════════════════════════════════════════════════╝');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: Sin cambios en base de datos');
        }

        // Obtener procesos que necesitan cálculo
        $query = ProcesoPrenda::query();

        if (!$fixAll) {
            // Solo procesos que no tengan dias_duracion calculado
            $query->whereNull('dias_duracion');
        }

        $query->whereNotNull('fecha_inicio')
            ->whereNotNull('fecha_fin');

        $procesos = $query->get();

        $this->info("📊 Procesos encontrados: " . $procesos->count());

        if ($procesos->isEmpty()) {
            $this->info('✓ No hay procesos que procesar.');
            return;
        }

        $progreso = $this->output->createProgressBar($procesos->count());
        $actualizados = 0;
        $errores = [];

        foreach ($procesos as $proceso) {
            try {
                $dias = CalculadorDiasService::calcularDiasHabiles(
                    $proceso->fecha_inicio,
                    $proceso->fecha_fin
                );

                $diasFormato = CalculadorDiasService::formatearDias($dias);

                if (!$dryRun) {
                    $proceso->update([
                        'dias_duracion' => $diasFormato,
                    ]);
                }

                $actualizados++;
            } catch (\Exception $e) {
                $errores[] = "Proceso #{$proceso->id}: " . $e->getMessage();
            }

            $progreso->advance();
        }

        $progreso->finish();
        $this->newLine(2);

        $this->info('╔════════════════════════════════╗');
        $this->info('║  RESUMEN                       ║');
        $this->info('╚════════════════════════════════╝');
        $this->newLine();

        $this->line("✓ Procesos actualizados: $actualizados");

        if (!empty($errores)) {
            $this->newLine();
            $this->error("⚠️  Errores encontrados: " . count($errores));
            foreach (array_slice($errores, 0, 5) as $error) {
                $this->error("   - $error");
            }
            if (count($errores) > 5) {
                $this->error("   ... y " . (count($errores) - 5) . " más");
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->warn('⚠️  Este fue un DRY-RUN. Sin cambios en la base de datos.');
            $this->info('Para ejecutar la actualización real:');
            $this->info('   php artisan procesos:calcular-dias');
        } else {
            $this->info('✅ Cálculo completado exitosamente!');
        }
    }
}
