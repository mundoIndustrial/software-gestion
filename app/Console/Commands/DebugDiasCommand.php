<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PedidoProduccion;
use App\Models\Festivo;
use App\Services\CacheCalculosService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DebugDiasCommand extends Command
{
    protected $signature = 'debug:dias';
    protected $description = 'Diagnosticar cálculo de días';

    public function handle()
    {
        $this->info("\n========================================");
        $this->info("DIAGNÓSTICO DE CÁLCULO DE DÍAS");
        $this->info("========================================\n");

        // 1. Verificar primeros 5 pedidos
        $pedidos = PedidoProduccion::limit(5)->get();
        $this->info("📋 Verificando primeros 5 pedidos:\n");

        foreach ($pedidos as $pedido) {
            $this->line("─────────────────────────────────────");
            $this->line("Pedido: {$pedido->numero_pedido}");
            $this->line("Estado: {$pedido->estado}");
            $this->line("Fecha creación: {$pedido->fecha_de_creacion_de_orden}");
            
            // Obtener procesos
            $procesos = DB::table('procesos_prenda')
                ->where('numero_pedido', $pedido->numero_pedido)
                ->orderBy('fecha_inicio', 'ASC')
                ->select('proceso', 'fecha_inicio', 'fecha_fin')
                ->get();
            
            $this->line("\n📊 Procesos encontrados: " . $procesos->count());
            
            if ($procesos->count() > 0) {
                foreach ($procesos as $i => $proc) {
                    $this->line("  [{$i}] {$proc->proceso}");
                    $this->line("      Inicio: {$proc->fecha_inicio}");
                    $this->line("      Fin: {$proc->fecha_fin}");
                }
                
                // Calcular días usando el servicio
                $dias = CacheCalculosService::getTotalDias($pedido->numero_pedido, $pedido->estado);
                $this->line("\n✅ Total de días calculados: {$dias}");
                
                // Cálculo manual para verificar
                $this->line("\n🔍 Verificación manual:");
                $festivos = Festivo::pluck('fecha')->toArray();
                $festivosSet = [];
                foreach ($festivos as $f) {
                    try {
                        $festivosSet[Carbon::parse($f)->format('Y-m-d')] = true;
                    } catch (\Exception $e) {}
                }
                
                $procesosFechas = $procesos->map(fn($p) => Carbon::parse($p->fecha_inicio))->toArray();
                $totalDiasManual = 0;
                
                foreach ($procesosFechas as $idx => $fechaInicio) {
                    $fechaFin = isset($procesosFechas[$idx + 1]) ? $procesosFechas[$idx + 1] : Carbon::now();
                    $diasSegmento = $this->calcularDiasHabiles($fechaInicio, $fechaFin, $festivosSet);
                    $this->line("  Proceso {$idx}: {$fechaInicio->format('Y-m-d')} → {$fechaFin->format('Y-m-d')} = {$diasSegmento} días");
                    $totalDiasManual += $diasSegmento;
                }
                
                $this->line("  TOTAL MANUAL: {$totalDiasManual} días");
                
                if ($dias === $totalDiasManual) {
                    $this->info("  ✅ Cálculos coinciden");
                } else {
                    $this->error("  ❌ MISMATCH: Servicio={$dias}, Manual={$totalDiasManual}");
                }
            } else {
                $this->warn("⚠️  NO HAY PROCESOS PARA ESTE PEDIDO");
            }
            
            $this->line("");
        }

        $this->info("========================================");
        $this->line("Festivos registrados: " . Festivo::count());
        $this->line("Total pedidos: " . PedidoProduccion::count());
        $this->line("Total procesos: " . DB::table('procesos_prenda')->count());
        $this->info("========================================\n");
    }

    private function calcularDiasHabiles(Carbon $inicio, Carbon $fin, $festivosSet): int
    {
        $current = $inicio->copy()->addDay();
        $totalDays = 0;
        $weekends = 0;
        $holidays = 0;
        
        $maxIterations = 3650;
        $iterations = 0;
        
        while ($current <= $fin && $iterations < $maxIterations) {
            $dateString = $current->format('Y-m-d');
            $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
            $isFestivo = isset($festivosSet[$dateString]);
            
            $totalDays++;
            if ($isWeekend) $weekends++;
            if ($isFestivo) $holidays++;
            
            $current->addDay();
            $iterations++;
        }
        
        return $totalDays - $weekends - $holidays;
    }
}
