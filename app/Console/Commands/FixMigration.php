<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMigration extends Command
{
    protected $signature = 'fix:migration';
    protected $description = 'Elimina registro de migración duplicado';

    public function handle()
    {
        DB::table('migrations')
            ->where('migration', '2025_12_12_create_reflectivo_cotizacion_table')
            ->delete();

        $this->info('✅ Registro de migración eliminado');
    }
}
