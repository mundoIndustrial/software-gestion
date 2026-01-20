<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerificarSecuenciaCommand extends Command
{
    protected $signature = 'verificar:secuencia';
    protected $description = 'Verifica que exista la secuencia universal de cotizaciones';

    public function handle()
    {
        $this->info('Verificando tabla numero_secuencias...');

        try {
            $todos = DB::table('numero_secuencias')->get();
            
            if ($todos->isEmpty()) {
                $this->warn('⚠️  Tabla numero_secuencias está vacía');
            } else {
                $this->info(' Contenido actual:');
                foreach ($todos as $row) {
                    $this->line("   Tipo: {$row->tipo}, Siguiente: {$row->siguiente}");
                }
            }
            
            // Verificar que existe universal
            $universal = DB::table('numero_secuencias')
                ->where('tipo', 'cotizaciones_universal')
                ->first();
            
            if (!$universal) {
                $this->warn('⚠️  Secuencia universal NO EXISTE, creando...');
                DB::table('numero_secuencias')->insert([
                    'tipo' => 'cotizaciones_universal',
                    'siguiente' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info(' Secuencia universal CREADA con valor inicial 1');
            } else {
                $this->info(" Secuencia universal ya existe (siguiente: {$universal->siguiente})");
            }
            
        } catch (\Exception $e) {
            $this->error(' Error: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
