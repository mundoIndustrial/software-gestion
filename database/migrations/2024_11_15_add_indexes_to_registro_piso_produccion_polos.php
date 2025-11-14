<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * ⚡ OPTIMIZACIÓN: Agregar índices para mejorar rendimiento en PRODUCCIÓN y POLOS
     */
    public function up(): void
    {
        // Índices para tabla registro_piso_produccion
        try {
            DB::statement('CREATE INDEX idx_registro_piso_produccion_fecha ON registro_piso_produccion(fecha)');
        } catch (\Exception $e) {
            \Log::warning('Índice ya existe: idx_registro_piso_produccion_fecha');
        }
        
        try {
            DB::statement('CREATE INDEX idx_registro_piso_produccion_orden_produccion ON registro_piso_produccion(orden_produccion)');
        } catch (\Exception $e) {
            \Log::warning('Índice ya existe: idx_registro_piso_produccion_orden_produccion');
        }

        // Índices para tabla registro_piso_polo
        try {
            DB::statement('CREATE INDEX idx_registro_piso_polo_fecha ON registro_piso_polo(fecha)');
        } catch (\Exception $e) {
            \Log::warning('Índice ya existe: idx_registro_piso_polo_fecha');
        }
        
        try {
            DB::statement('CREATE INDEX idx_registro_piso_polo_orden_produccion ON registro_piso_polo(orden_produccion)');
        } catch (\Exception $e) {
            \Log::warning('Índice ya existe: idx_registro_piso_polo_orden_produccion');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacer nada al revertir (los índices son útiles mantenerlos)
    }
};
?>
