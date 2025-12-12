<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeTables extends Command
{
    protected $signature = 'analyze:tables';
    protected $description = 'Analiza la estructura de las tablas para control de calidad';

    public function handle()
    {
        $this->line("\n=== ANÁLISIS DE TABLAS ===\n");

        // 1. Analizar estructura de pedidos_produccion
        $this->line("1. TABLA: pedidos_produccion");
        $this->line("----------------------------");
        
        $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pedidos_produccion'");
        foreach ($columns as $col) {
            $this->line("  - {$col->COLUMN_NAME}: {$col->COLUMN_TYPE} (nullable: {$col->IS_NULLABLE})");
        }

        $sample = DB::table('pedidos_produccion')->first();
        $this->line("\nPrimer registro:");
        if ($sample) {
            foreach ((array)$sample as $key => $value) {
                $val = is_null($value) ? 'NULL' : substr($value, 0, 50);
                $this->line("  $key: $val");
            }
        }

        // Valores únicos en área
        $this->line("\nValores de 'area':");
        $areas = DB::table('pedidos_produccion')->distinct()->pluck('area')->all();
        foreach ($areas as $area) {
            $count = DB::table('pedidos_produccion')->where('area', $area)->count();
            $this->line("  - '$area': $count registros");
        }

        // 2. Analizar registros_por_orden_bodega
        $this->line("\n\n2. TABLA: registros_por_orden_bodega");
        $this->line("-------------------------------------");
        
        $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registros_por_orden_bodega'");
        foreach ($columns as $col) {
            $this->line("  - {$col->COLUMN_NAME}: {$col->COLUMN_TYPE} (nullable: {$col->IS_NULLABLE})");
        }

        $sample = DB::table('registros_por_orden_bodega')->first();
        $this->line("\nPrimer registro:");
        if ($sample) {
            foreach ((array)$sample as $key => $value) {
                $val = is_null($value) ? 'NULL' : substr($value, 0, 50);
                $this->line("  $key: $val");
            }
        }

        $this->line("\nTotal registros: " . DB::table('registros_por_orden_bodega')->count());

        // 3. Analizar proceso_prendas
        $this->line("\n\n3. TABLA: proceso_prendas");
        $this->line("-------------------------");
        
        if (DB::connection()->getSchemaBuilder()->hasTable('proceso_prendas')) {
            $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'proceso_prendas'");
            foreach ($columns as $col) {
                $this->line("  - {$col->COLUMN_NAME}: {$col->COLUMN_TYPE} (nullable: {$col->IS_NULLABLE})");
            }

            $sample = DB::table('proceso_prendas')->first();
            $this->line("\nPrimer registro:");
            if ($sample) {
                foreach ((array)$sample as $key => $value) {
                    $val = is_null($value) ? 'NULL' : substr($value, 0, 50);
                    $this->line("  $key: $val");
                }
            }

            // Valores únicos en área
            if (DB::connection()->getSchemaBuilder()->hasColumn('proceso_prendas', 'area')) {
                $this->line("\nValores de 'area':");
                $areas = DB::table('proceso_prendas')->distinct()->pluck('area')->all();
                foreach ($areas as $area) {
                    if (!is_null($area)) {
                        $count = DB::table('proceso_prendas')->where('area', $area)->count();
                        $this->line("  - '$area': $count registros");
                    }
                }
            }

            $this->line("\nTotal registros: " . DB::table('proceso_prendas')->count());
        } else {
            $this->line("Tabla 'proceso_prendas' no existe");
        }

        $this->line("\n=== FIN ANÁLISIS ===\n");
    }
}
