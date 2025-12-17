<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckTelaFotosStructure extends Command
{
    protected $signature = 'check:tela-fotos-structure';
    protected $description = 'Verificar estructura de tablas de fotos de telas';

    public function handle()
    {
        $this->info("═══════════════════════════════════════════════════════════════");
        $this->info("ESTRUCTURA DE prenda_tela_fotos_cot");
        $this->info("═══════════════════════════════════════════════════════════════\n");

        $columns = DB::select("DESCRIBE prenda_tela_fotos_cot");
        foreach ($columns as $col) {
            $this->line("  - {$col->Field} ({$col->Type}) - Null: {$col->Null} - Key: {$col->Key}");
        }

        $this->info("\n═══════════════════════════════════════════════════════════════");
        $this->info("ESTRUCTURA DE prenda_fotos_tela_pedido");
        $this->info("═══════════════════════════════════════════════════════════════\n");

        $columns2 = DB::select("DESCRIBE prenda_fotos_tela_pedido");
        foreach ($columns2 as $col) {
            $this->line("  - {$col->Field} ({$col->Type}) - Null: {$col->Null} - Key: {$col->Key}");
        }

        $this->info("\n═══════════════════════════════════════════════════════════════");
        $this->info("RELACIONES Y FOREIGN KEYS");
        $this->info("═══════════════════════════════════════════════════════════════\n");

        $fks = DB::select("SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME IN ('prenda_tela_fotos_cot', 'prenda_fotos_tela_pedido')
        AND REFERENCED_TABLE_NAME IS NOT NULL");

        foreach ($fks as $fk) {
            $this->line("  {$fk->TABLE_NAME}.{$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}");
        }

        $this->info("\n═══════════════════════════════════════════════════════════════");
        $this->info("DATOS EN prenda_tela_fotos_cot");
        $this->info("═══════════════════════════════════════════════════════════════\n");

        $data = DB::select("SELECT * FROM prenda_tela_fotos_cot LIMIT 1");
        if (!empty($data)) {
            $row = (array)$data[0];
            foreach ($row as $col => $val) {
                $this->line("  $col: " . ($val ?? 'NULL'));
            }
        } else {
            $this->line("Sin datos");
        }

        // Verificar si hay prenda_tela_cot_id
        $this->info("\n═══════════════════════════════════════════════════════════════");
        $this->info("VERIFICACIÓN: ¿Existe prenda_tela_cot_id en prenda_tela_fotos_cot?");
        $this->info("═══════════════════════════════════════════════════════════════\n");

        $result = DB::select("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'prenda_tela_fotos_cot' AND COLUMN_NAME = 'prenda_tela_cot_id'");
        if ($result[0]->count > 0) {
            $this->info("✅ SÍ existe prenda_tela_cot_id");
        } else {
            $this->error("❌ NO existe prenda_tela_cot_id");
        }
    }
}
