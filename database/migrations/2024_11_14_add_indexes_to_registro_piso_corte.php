<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ⚡ OPTIMIZACIÓN: Agregar índices adicionales para mejorar rendimiento
     */
    public function up(): void
    {
        // ⚡ OPTIMIZACIÓN: Agregar índices para mejorar rendimiento
        // Usar try-catch para manejar índices que ya puedan existir
        try {
            DB::statement('CREATE INDEX idx_registro_piso_corte_fecha ON registro_piso_corte(fecha)');
        } catch (\Exception $e) {
            \Log::warning('Índice ya existe: idx_registro_piso_corte_fecha');
        }
        
        try {
            DB::statement('CREATE INDEX idx_registro_piso_corte_orden_produccion ON registro_piso_corte(orden_produccion)');
        } catch (\Exception $e) {
            \Log::warning('Índice ya existe: idx_registro_piso_corte_orden_produccion');
        }
        
        try {
            DB::statement('CREATE INDEX idx_registro_piso_corte_fecha_hora ON registro_piso_corte(fecha, hora_id)');
        } catch (\Exception $e) {
            \Log::warning('Índice ya existe: idx_registro_piso_corte_fecha_hora');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada al revertir (los índices son útiles mantenerlos)
        // Si realmente queremos eliminarlos:
        // DB::statement('DROP INDEX idx_registro_piso_corte_fecha ON registro_piso_corte');
        // DB::statement('DROP INDEX idx_registro_piso_corte_orden_produccion ON registro_piso_corte');
        // DB::statement('DROP INDEX idx_registro_piso_corte_fecha_hora ON registro_piso_corte');
    }
};
