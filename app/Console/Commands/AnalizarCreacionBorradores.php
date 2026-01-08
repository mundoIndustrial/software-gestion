<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;
use App\Models\User;

class AnalizarCreacionBorradores extends Command
{
    protected $signature = 'analizar:borradores {asesor_id?}';
    protected $description = 'Analizar patrones de creación de borradores';

    public function handle()
    {
        $asesor_id = $this->argument('asesor_id');

        $this->info('=== ANALIZANDO CREACIÓN DE BORRADORES ===\n');

        if (!$asesor_id) {
            $this->info('Listando asesores y cantidad de cotizaciones:');
            $asesores = User::all()->pluck('id', 'name');
            foreach ($asesores as $nombre => $id) {
                $total = Cotizacion::where('asesor_id', $id)->count();
                $borradores = Cotizacion::where('asesor_id', $id)->where('es_borrador', true)->count();
                $enviadas = Cotizacion::where('asesor_id', $id)->where('es_borrador', false)->count();
                $this->line("  ID: {$id} | {$nombre} | Total: {$total} | Borradores: {$borradores} | Enviadas: {$enviadas}");
            }
            return;
        }

        // Analizar asesor específico
        $asesor = User::find($asesor_id);
        if (!$asesor) {
            $this->error("Asesor no encontrado: $asesor_id");
            return;
        }

        $this->info("ASESOR: {$asesor->name} (ID: {$asesor_id})\n");

        $cotizaciones = Cotizacion::where('asesor_id', $asesor_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->info("Total de cotizaciones: " . $cotizaciones->count() . "\n");

        // Agrupar por fecha de creación (hasta el minuto)
        $agrupadas = $cotizaciones->groupBy(function($cot) {
            return $cot->created_at->format('Y-m-d H:i');
        });

        $this->info("=== COTIZACIONES AGRUPADAS POR MINUTO ===\n");
        
        foreach ($agrupadas as $minuto => $grupo) {
            if (count($grupo) > 1) {
                $this->warn("⚠️  {$minuto} - {$grupo->count()} cotizaciones creadas al MISMO MINUTO");
                foreach ($grupo as $cot) {
                    $estado = $cot->es_borrador ? 'BORRADOR' : $cot->estado;
                    $this->line("    - ID: {$cot->id} | NUM: {$cot->numero_cotizacion} | ESTADO: {$estado} | {$cot->created_at->format('H:i:s')}");
                }
                $this->line('');
            }
        }

        // Estadísticas
        $this->info("=== ESTADÍSTICAS ===\n");
        $totalBorradores = $cotizaciones->where('es_borrador', true)->count();
        $totalEnviadas = $cotizaciones->where('es_borrador', false)->count();
        
        $this->line("Total de borradores: $totalBorradores");
        $this->line("Total de enviadas: $totalEnviadas");

        // Ver historial de cambios si existe
        $this->info("\n=== ÚLTIMAS 10 COTIZACIONES CREADAS ===\n");
        foreach ($cotizaciones->take(10) as $cot) {
            $estado = $cot->es_borrador ? 'BORRADOR' : $cot->estado;
            $cliente = $cot->cliente ? $cot->cliente->nombre : 'SIN CLIENTE';
            $this->line("ID: {$cot->id} | NUM: {$cot->numero_cotizacion} | ESTADO: {$estado} | CLIENTE: {$cliente} | CREADA: {$cot->created_at->format('Y-m-d H:i:s')}");
        }
    }
}
