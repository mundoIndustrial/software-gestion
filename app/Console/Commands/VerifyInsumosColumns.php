<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class VerifyInsumosColumns extends Command
{
    protected $signature = 'verify:insumos-columns';
    protected $description = 'Verifica que las columnas de insumos se crearon correctamente';

    public function handle()
    {
        $this->info("\n╔════════════════════════════════════════════════════════════════╗");
        $this->info("║  VERIFICACIÓN DE COLUMNAS - materiales_orden_insumos           ║");
        $this->info("╚════════════════════════════════════════════════════════════════╝\n");

        $table = 'materiales_orden_insumos';
        $nuevasColumnas = ['fecha_orden', 'fecha_pago', 'fecha_despacho', 'observaciones', 'dias_demora'];
        $encontradas = [];

        foreach ($nuevasColumnas as $column) {
            if (Schema::hasColumn($table, $column)) {
                $encontradas[] = $column;
                $this->line("✅ <fg=green>" . str_pad($column, 25) . "</> | Columna encontrada");
            }
        }

        $this->line("\n" . str_repeat("─", 66));
        $this->info("📊 RESUMEN:");
        $this->line("   Columnas encontradas: " . count($encontradas) . " / " . count($nuevasColumnas) . "\n");

        if (count($encontradas) === count($nuevasColumnas)) {
            $this->info("✅ ¡TODAS LAS COLUMNAS SE CREARON CORRECTAMENTE!");
            $this->line("\n📋 COLUMNAS CREADAS:");
            foreach ($encontradas as $col) {
                $this->line("   ✅ " . $col);
            }
            return 0;
        } else {
            $this->error("⚠️  Columnas faltantes:");
            foreach ($nuevasColumnas as $col) {
                if (!in_array($col, $encontradas)) {
                    $this->line("   ❌ " . $col);
                }
            }
            return 1;
        }
    }
}
