<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ListarProcesosCommand extends Command
{
    protected $signature = 'procesos:listar';
    protected $description = 'Lista todos los tipos de procesos disponibles';

    public function handle()
    {
        $this->line("\n═══════════════════════════════════════════════════");
        $this->line("  TIPOS DE PROCESOS DISPONIBLES EN EL SISTEMA");
        $this->line("═══════════════════════════════════════════════════\n");

        // Buscar tabla de procesos
        $tables = Schema::getTableListing();
        $procesosTables = array_filter($tables, function($table) {
            return stripos($table, 'proceso') !== false;
        });

        $this->info("Tablas encontradas con 'proceso':");
        foreach ($procesosTables as $table) {
            $this->line("  • {$table}");
        }

        // Buscar procesos_prenda
        if (in_array('procesos_prenda', $tables)) {
            $this->line("\n▸ Contenido de tabla 'procesos_prenda':\n");
            $procesos = DB::table('procesos_prenda')->get();
            
            if ($procesos->isEmpty()) {
                $this->warn("⚠ Tabla vacía");
            } else {
                $this->info("Total registros: " . $procesos->count());
                foreach ($procesos as $p) {
                    $this->line("  ────────────────────────────────────");
                    $this->line("  ID: {$p->id}");
                    $this->line("  Nombre: {$p->nombre}");
                    if (isset($p->tipo)) {
                        $this->line("  Tipo: {$p->tipo}");
                    }
                    if (isset($p->slug)) {
                        $this->line("  Slug: {$p->slug}");
                    }
                }
            }
        } else {
            $this->warn("⚠ Tabla procesos_prenda no existe");
        }

        // Ver tipos_procesos si existe
        if (in_array('tipos_procesos', $tables)) {
            $this->line("\n▸ Contenido de tabla 'tipos_procesos':\n");
            $tipos = DB::table('tipos_procesos')->get();
            
            if ($tipos->isEmpty()) {
                $this->warn("⚠ Tabla vacía");
            } else {
                $this->info("Total registros: " . $tipos->count());
                foreach ($tipos as $t) {
                    $this->line("  ────────────────────────────────────");
                    $this->line("  ID: {$t->id}");
                    $this->line("  Nombre: {$t->nombre}");
                }
            }
        } else {
            $this->warn("⚠ Tabla tipos_procesos no existe");
        }

        // Buscar en pedidos_procesos_prenda_detalles
        $this->line("\n▸ RESUMEN: Procesos utilizados en pedido 50:\n");
        
        $procesosUsados = DB::table('pedidos_procesos_prenda_detalles as ppd')
            ->join('prendas_pedido as pp', 'ppd.prenda_pedido_id', '=', 'pp.id')
            ->where('pp.pedido_produccion_id', 50)
            ->select('ppd.tipo_proceso_id', 'ppd.estado', DB::raw('COUNT(*) as total'))
            ->groupBy('ppd.tipo_proceso_id', 'ppd.estado')
            ->get();

        if ($procesosUsados->isEmpty()) {
            $this->warn("⚠ Sin procesos en pedido 50");
        } else {
            foreach ($procesosUsados as $pu) {
                $this->line("  Tipo Proceso ID: {$pu->tipo_proceso_id} | Estado: {$pu->estado} | Cantidad: {$pu->total}");
            }
        }

        $this->line("\n");
    }
}
