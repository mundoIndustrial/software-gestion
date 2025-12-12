<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeAllTables extends Command
{
    protected $signature = 'analyze:all-tables';
    protected $description = 'Lista todas las tablas disponibles';

    public function handle()
    {
        $this->line("\n=== TODAS LAS TABLAS DISPONIBLES ===\n");

        $tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");
        
        foreach ($tables as $table) {
            $this->line("  - {$table->TABLE_NAME}");
        }

        $this->line("\n=== BUSCANDO TABLA DE PROCESOS ===\n");

        $processTables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE '%proceso%' ORDER BY TABLE_NAME");
        
        if (count($processTables) > 0) {
            foreach ($processTables as $table) {
                $this->line("\nEncontrada: {$table->TABLE_NAME}");
                
                // Mostrar columnas
                $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table->TABLE_NAME}'");
                $this->line("  Columnas:");
                foreach ($columns as $col) {
                    $this->line("    - {$col->COLUMN_NAME}: {$col->COLUMN_TYPE}");
                }

                // Primer registro
                $sample = DB::table($table->TABLE_NAME)->first();
                if ($sample) {
                    $this->line("  Primer registro:");
                    foreach ((array)$sample as $key => $value) {
                        $val = is_null($value) ? 'NULL' : substr($value, 0, 50);
                        $this->line("    $key: $val");
                    }
                }

                $count = DB::table($table->TABLE_NAME)->count();
                $this->line("  Total registros: $count");
            }
        } else {
            $this->line("No se encontraron tablas con 'proceso' en el nombre");
        }

        $this->line("\n=== FIN ===\n");
    }
}
