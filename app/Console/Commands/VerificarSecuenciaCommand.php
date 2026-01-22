<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VerificarSecuenciaCommand extends Command
{
    protected $signature = 'verificar:secuencia';
    protected $description = 'Verifica que exista la secuencia universal de cotizaciones';

    public function handle()
    {
        // Solo mostrar output en modo interactivo (no en seeds/jobs)
        $verbose = $this->getOutput()->isVerbose();
        
        if ($verbose) {
            $this->info('Verificando tabla numero_secuencias...');
        }

        try {
            $todos = DB::table('numero_secuencias')->get();
            
            if ($todos->isEmpty()) {
                if ($verbose) {
                    $this->warn('  Tabla numero_secuencias está vacía');
                }
            } else {
                if ($verbose) {
                    $this->info(' Contenido actual:');
                    foreach ($todos as $row) {
                        $this->line("   Tipo: {$row->tipo}, Siguiente: {$row->siguiente}");
                    }
                }
            }
            
            // Verificar que existe universal
            $universal = DB::table('numero_secuencias')
                ->where('tipo', 'cotizaciones_universal')
                ->first();
            
            if (!$universal) {
                if ($verbose) {
                    $this->warn('  Secuencia universal NO EXISTE, creando...');
                }
                DB::table('numero_secuencias')->insert([
                    'tipo' => 'cotizaciones_universal',
                    'siguiente' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                if ($verbose) {
                    $this->info(' Secuencia universal CREADA con valor inicial 1');
                }
            } else {
                if ($verbose) {
                    $this->info(" Secuencia universal ya existe (siguiente: {$universal->siguiente})");
                }
                // Log silenciosamente que la secuencia existe
                Log::debug("Secuencia universal verificada: siguiente = {$universal->siguiente}");
            }
            
        } catch (\Exception $e) {
            $this->error(' Error: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
