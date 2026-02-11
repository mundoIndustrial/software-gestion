<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;

class AsignarAsesorACotizaciones extends Command
{
    protected $signature = 'asignar:asesor {asesor_id : ID del asesor} {--cotizacion_ids= : IDs de cotizaciones separadas por coma}';
    protected $description = 'Asignar asesor_id a cotizaciones sin asesor';

    public function handle()
    {
        $asesorId = $this->argument('asesor_id');
        $cotizacionIds = $this->option('cotizacion_ids');

        $this->info(" ASIGNANDO ASESOR_ID A COTIZACIONES");
        $this->line('');

        // Validar que el asesor existe
        $asesor = \App\Models\User::find($asesorId);
        if (!$asesor) {
            $this->error(" El asesor con ID {$asesorId} no existe");
            return 1;
        }

        $this->info(" Asesor encontrado: {$asesor->name} (ID: {$asesorId})");
        $this->line('');

        // Construir query
        $query = Cotizacion::whereNull('asesor_id');

        // Si se especifican IDs, filtrar por ellas
        if ($cotizacionIds) {
            $ids = array_map('intval', explode(',', $cotizacionIds));
            $query->whereIn('id', $ids);
            $this->info("Filtrando por IDs: " . implode(', ', $ids));
        } else {
            $this->warn("  Asignando a TODAS las cotizaciones sin asesor_id");
        }

        $this->line('');

        // Contar cotizaciones a actualizar
        $total = $query->count();
        if ($total === 0) {
            $this->warn("No hay cotizaciones para actualizar");
            return 0;
        }

        $this->info(" Cotizaciones a actualizar: {$total}");
        $this->line('');

        // Confirmar
        if (!$this->confirm("¿Deseas continuar?")) {
            $this->info("Operación cancelada");
            return 0;
        }

        // Actualizar
        $updated = $query->update(['asesor_id' => $asesorId]);

        $this->line('');
        $this->info(" Actualización completada");
        $this->info("   Cotizaciones actualizadas: {$updated}");

        return 0;
    }
}
