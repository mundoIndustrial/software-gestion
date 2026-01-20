<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AnalyzeDatabaseStructure extends Command
{
    protected $signature = 'analyze:database';
    protected $description = 'Analizar la estructura completa de la base de datos';

    public function handle()
    {
        $this->info('════════════════════════════════════════════════════════');
        $this->info('ANÁLISIS COMPLETO DE LA ESTRUCTURA DE BASE DE DATOS');
        $this->info('════════════════════════════════════════════════════════');
        $this->newLine();

        // 1. Obtener todas las tablas
        $tables = DB::select('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()');
        
        $this->info(" TOTAL DE TABLAS: " . count($tables));
        $this->newLine();

        // 2. Tablas relacionadas con cotizaciones
        $this->line('═════════════════════════════════════════════════════════');
        $this->info(' TABLAS RELACIONADAS CON COTIZACIONES');
        $this->line('═════════════════════════════════════════════════════════');
        $this->newLine();

        $cotizacionTables = ['cotizaciones', 'prendas_pedido', 'prenda_telas', 'prenda_fotos_tela_pedido', 'logo_pedidos', 'fotos_prenda'];

        foreach ($cotizacionTables as $tableName) {
            $this->showTableStructure($tableName);
        }

        // 3. Todas las tablas listadas
        $this->newLine();
        $this->line('═════════════════════════════════════════════════════════');
        $this->info(' TODAS LAS TABLAS DE LA BD');
        $this->line('═════════════════════════════════════════════════════════');
        $this->newLine();

        foreach ($tables as $table) {
            $tableName = $table->TABLE_NAME;
            $this->line("  • $tableName");
        }

        $this->newLine();
    }

    protected function showTableStructure($tableName)
    {
        // Verificar si la tabla existe
        try {
            $columns = DB::select("
                SELECT 
                    COLUMN_NAME,
                    COLUMN_TYPE,
                    IS_NULLABLE,
                    COLUMN_KEY,
                    EXTRA
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
            ", [$tableName]);

            if (empty($columns)) {
                $this->line(" Tabla '$tableName' NO EXISTE");
                return;
            }

            $this->info(" Tabla: $tableName");
            $this->line("   ┌─────────────────────────────────────────────────────────┐");

            foreach ($columns as $col) {
                $nullable = $col->IS_NULLABLE === 'YES' ? '✓' : '✗';
                $key = $col->COLUMN_KEY ? "[{$col->COLUMN_KEY}]" : '';
                $extra = $col->EXTRA ? "({$col->EXTRA})" : '';
                
                $this->line(sprintf(
                    "   │ %-20s | %-30s | NULL:%s %s %s",
                    $col->COLUMN_NAME,
                    $col->COLUMN_TYPE,
                    $nullable,
                    $key,
                    $extra
                ));
            }

            $this->line("   └─────────────────────────────────────────────────────────┘");

            // Contar registros
            $count = DB::table($tableName)->count();
            $this->line("    Registros: $count");
            
            $this->newLine();

        } catch (\Exception $e) {
            $this->error(" Error analizando tabla '$tableName': " . $e->getMessage());
            $this->newLine();
        }
    }
}
