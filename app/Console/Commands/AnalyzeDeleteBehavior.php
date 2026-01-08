<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cotizacion;

class AnalyzeDeleteBehavior extends Command
{
    protected $signature = 'analizar:delete-behavior';
    protected $description = 'Analizar qué pasa cuando se borra una cotización';

    public function handle()
    {
        $this->info('=== ANALIZANDO COMPORTAMIENTO DE DELETE ===\n');

        // Obtener todas las cotizaciones (incluyendo soft-deleted)
        $todas = Cotizacion::withTrashed()->orderBy('created_at', 'desc')->limit(20)->get();

        $this->info("Total de cotizaciones (incluyendo borradas): " . $todas->count() . "\n");

        foreach ($todas as $cot) {
            $estado = $cot->es_borrador ? 'BORRADOR' : $cot->estado;
            $eliminada = $cot->trashed() ? '❌ ELIMINADA' : '✅ ACTIVA';
            $cliente = $cot->cliente ? $cot->cliente->nombre : 'SIN CLIENTE';
            
            $this->line("ID: {$cot->id} | NUM: {$cot->numero_cotizacion} | ESTADO: {$estado} | {$eliminada} | CLIENTE: {$cliente} | CREADA: {$cot->created_at->format('Y-m-d H:i:s')}");
        }

        $this->info("\n=== ANÁLISIS DE DUPLICADOS ===\n");
        
        // Buscar pares de cotizaciones creadas en el mismo minuto
        $agrupadas = Cotizacion::withTrashed()
            ->get()
            ->groupBy(function($cot) {
                return $cot->created_at->format('Y-m-d H:i');
            });

        foreach ($agrupadas as $minuto => $grupo) {
            if ($grupo->count() > 1) {
                $this->warn("⚠️  Minuto: {$minuto} - {$grupo->count()} cotizaciones");
                foreach ($grupo as $cot) {
                    $eliminada = $cot->trashed() ? '❌ ELIMINADA' : '✅ ACTIVA';
                    $estado = $cot->es_borrador ? 'BORRADOR' : $cot->estado;
                    $this->line("    ID: {$cot->id} | NUM: {$cot->numero_cotizacion} | {$eliminada} | {$estado}");
                }
            }
        }
    }
}
