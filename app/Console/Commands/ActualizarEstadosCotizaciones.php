<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;

class ActualizarEstadosCotizaciones extends Command
{
    protected $signature = 'actualizar:estados-cotizaciones {--usuario_id= : ID del usuario (opcional)}';
    protected $description = 'Actualizar estados de cotizaciones a valores válidos del enum';

    public function handle()
    {
        $this->info('🔄 ACTUALIZANDO ESTADOS DE COTIZACIONES');
        $this->line('');

        // Mapeo de estados inválidos a válidos
        $estadosMap = [
            'enviada' => 'ENVIADA_CONTADOR',
            'ENVIADA_CONTADOR' => 'ENVIADA_CONTADOR',
            'BORRADOR' => 'BORRADOR',
            'borrador' => 'BORRADOR',
            'aprobada' => 'APROBADA_CONTADOR',
            'APROBADA_CONTADOR' => 'APROBADA_CONTADOR',
        ];

        // Construir query
        $query = Cotizacion::query();

        if ($this->option('usuario_id')) {
            $query->where('asesor_id', $this->option('usuario_id'));
            $this->info("Filtrando por usuario: {$this->option('usuario_id')}");
        }

        $this->line('');

        // Obtener estados únicos actuales
        $estadosActuales = $query->select('estado')->distinct()->pluck('estado')->toArray();
        $this->info("Estados actuales en la BD: " . implode(', ', $estadosActuales));
        $this->line('');

        // Actualizar cada estado
        $totalActualizadas = 0;
        foreach ($estadosMap as $estadoViejo => $estadoNuevo) {
            $count = Cotizacion::where('estado', $estadoViejo);
            
            if ($this->option('usuario_id')) {
                $count->where('asesor_id', $this->option('usuario_id'));
            }

            $count = $count->count();

            if ($count > 0) {
                Cotizacion::where('estado', $estadoViejo);
                
                if ($this->option('usuario_id')) {
                    Cotizacion::where('asesor_id', $this->option('usuario_id'));
                }

                Cotizacion::where('estado', $estadoViejo)->update(['estado' => $estadoNuevo]);
                
                $this->info("✅ '{$estadoViejo}' → '{$estadoNuevo}': {$count} cotizaciones");
                $totalActualizadas += $count;
            }
        }

        $this->line('');
        $this->info("✅ Total actualizado: {$totalActualizadas} cotizaciones");

        // Verificar estados finales
        $this->line('');
        $this->info('📊 ESTADOS FINALES:');
        $estadosFinales = Cotizacion::select('estado')->distinct()->pluck('estado')->toArray();
        foreach ($estadosFinales as $estado) {
            $count = Cotizacion::where('estado', $estado)->count();
            $this->info("   {$estado}: {$count}");
        }
    }
}
