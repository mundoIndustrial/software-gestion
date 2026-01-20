<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerificarTiposCotizacion extends Command
{
    protected $signature = 'db:verificar-tipos-cotizacion';
    protected $description = 'Verifica los tipos de cotización disponibles';

    public function handle()
    {
        $this->info(' VERIFICANDO TIPOS DE COTIZACIÓN');
        $this->newLine();

        $tipos = DB::table('tipos_cotizacion')->get();

        if ($tipos->isEmpty()) {
            $this->warn(' No hay tipos de cotización registrados');
            return;
        }

        $this->line(' Tipos de Cotización:');
        $this->newLine();

        foreach ($tipos as $tipo) {
            $this->line("ID: <fg=cyan>{$tipo->id}</>");
            $this->line("   Código: <fg=yellow>{$tipo->codigo}</>");
            $this->line("   Nombre: <fg=green>{$tipo->nombre}</>");
            $this->line("   Descripción: {$tipo->descripcion}");
            $this->line("   Activo: " . ($tipo->activo ? '' : ''));
            $this->newLine();
        }

        // Resumen
        $this->line(' RESUMEN:');
        $this->line("   Total: {$tipos->count()} tipos");
        $activos = $tipos->where('activo', true)->count();
        $this->line("   Activos: {$activos}");
        $this->newLine();

        // Información específica según tu comentario
        $this->line('📌 INFORMACIÓN IMPORTANTE:');
        $p = $tipos->where('codigo', 'P')->first();
        $b = $tipos->where('codigo', 'B')->first();
        $pb = $tipos->where('codigo', 'PB')->first();

        if ($p) {
            $this->line("   • P (Prenda): ID = <fg=cyan>{$p->id}</>");
        }
        if ($b) {
            $this->line("   • B (Logo/Bordado): ID = <fg=cyan>{$b->id}</>");
        }
        if ($pb) {
            $this->line("   • PB (Prenda + Logo): ID = <fg=cyan>{$pb->id}</>");
        }

        $this->newLine();
        $this->info(' VERIFICACIÓN COMPLETADA');
    }
}
