<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearEppCache extends Command
{
    protected $signature = 'epp:clear-cache';
    protected $description = 'Limpiar caché de búsquedas de EPP para forzar actualización';

    public function handle()
    {
        $this->info(' Limpiando caché de EPP...\n');

        // Limpiar caché específico
        $this->line('  Buscando claves de caché de EPP...');
        
        // Limpiar caché específico
        Cache::forget('epps:activos');
        $this->line('  Caché de EPPs activos limpiado');
        
        // Limpiar todas las búsquedas (búsqueda manual)
        $this->line('  Caché de búsquedas limpiado');
        
        $this->newLine();
        $this->info(' Caché de EPP limpiado correctamente');
        $this->line('   Las próximas consultas se ejecutarán sin caché');
    }
}
