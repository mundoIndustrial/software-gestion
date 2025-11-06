<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RegistroPisoCorte;

class CalcularMetaEficienciaCorte extends Command
{
    protected $signature = 'corte:calcular-meta-eficiencia';
    protected $description = 'Calcula y actualiza la meta y eficiencia para todos los registros de corte';

    public function handle()
    {
        $this->info('Iniciando cálculo de meta y eficiencia para todos los registros de corte...');
        
        $registros = RegistroPisoCorte::all();
        $total = $registros->count();
        $actualizados = 0;
        $errores = 0;
        
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        
        foreach ($registros as $registro) {
            try {
                // Calculate tiempo_para_programada
                $tiempo_para_programada = 0;
                if ($registro->paradas_programadas === 'DESAYUNO' || $registro->paradas_programadas === 'MEDIA TARDE') {
                    $tiempo_para_programada = 900; // 15 minutes in seconds
                } elseif ($registro->paradas_programadas === 'NINGUNA') {
                    $tiempo_para_programada = 0;
                }
                
                // Calculate tiempo_extendido
                $tiempo_extendido = 0;
                $tipo_extendido_lower = strtolower($registro->tipo_extendido ?? '');
                
                if (str_contains($tipo_extendido_lower, 'largo')) {
                    $tiempo_extendido = 40 * ($registro->numero_capas ?? 0);
                } elseif (str_contains($tipo_extendido_lower, 'corto')) {
                    $tiempo_extendido = 25 * ($registro->numero_capas ?? 0);
                }
                
                // Calculate tiempo_disponible
                $tiempo_disponible = (3600 * $registro->porcion_tiempo * $registro->numero_operarios) -
                                   $tiempo_para_programada -
                                   ($registro->tiempo_parada_no_programada ?? 0) -
                                   $tiempo_extendido -
                                   ($registro->tiempo_trazado ?? 0);
                
                $tiempo_disponible = max(0, $tiempo_disponible);
                
                // Calculate meta and eficiencia
                $actividad_lower = strtolower($registro->actividad ?? '');
                if (str_contains($actividad_lower, 'extender') || str_contains($actividad_lower, 'trazar')) {
                    $meta = $registro->cantidad;
                    $eficiencia = 1;
                } else {
                    $meta = $registro->tiempo_ciclo > 0 ? $tiempo_disponible / $registro->tiempo_ciclo : 0;
                    $eficiencia = $meta > 0 ? $registro->cantidad / $meta : 0;
                }
                
                // Update registro
                $registro->update([
                    'tiempo_disponible' => $tiempo_disponible,
                    'meta' => $meta,
                    'eficiencia' => $eficiencia,
                ]);
                
                $actualizados++;
            } catch (\Exception $e) {
                $errores++;
                $this->error("\nError en registro ID {$registro->id}: " . $e->getMessage());
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("✅ Proceso completado!");
        $this->info("Total de registros: {$total}");
        $this->info("Actualizados exitosamente: {$actualizados}");
        if ($errores > 0) {
            $this->warn("Errores encontrados: {$errores}");
        }
        
        return 0;
    }
}
