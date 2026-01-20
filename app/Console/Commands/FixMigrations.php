<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMigrations extends Command
{
    protected $signature = 'migrations:fix';
    protected $description = 'Marca migraciones antiguas como ejecutadas para evitar conflictos';

    public function handle()
    {
        $this->info(' Marcando todas las migraciones del 2026 como ejecutadas...\n');

        // Obtener todas las migraciones que no están registradas
        $archivos = glob(database_path('migrations/2026_*.php'));
        
        $batch = DB::table('migrations')->max('batch') ?? 0;
        $contador = 0;

        foreach ($archivos as $archivo) {
            $migracion = basename($archivo, '.php');
            
            // Verificar si ya está registrada
            $existe = DB::table('migrations')->where('migration', $migracion)->exists();

            if (!$existe) {
                DB::table('migrations')->insert([
                    'migration' => $migracion,
                    'batch' => $batch + 1,
                ]);
                $contador++;
            }
        }

        $this->info(" {$contador} migraciones marcadas como ejecutadas\n");
        
        $this->info(' Todas las migraciones están sincronizadas');

        return 0;
    }
}
