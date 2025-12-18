<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agrega solo los índices faltantes para mejorar rendimiento
     */
    public function up(): void
    {
        // 1. cotizaciones - Índices para filtros comunes
        if (!$this->indexExists('cotizaciones', 'cotizaciones_asesor_id_index')) {
            DB::statement('CREATE INDEX cotizaciones_asesor_id_index ON cotizaciones (asesor_id)');
        }
        
        if (!$this->indexExists('cotizaciones', 'cotizaciones_tipo_index')) {
            DB::statement('CREATE INDEX cotizaciones_tipo_index ON cotizaciones (tipo)');
        }
        
        if (!$this->indexExists('cotizaciones', 'cotizaciones_es_borrador_index')) {
            DB::statement('CREATE INDEX cotizaciones_es_borrador_index ON cotizaciones (es_borrador)');
        }
        
        if (!$this->indexExists('cotizaciones', 'cotizaciones_asesor_tipo_borrador_index')) {
            DB::statement('CREATE INDEX cotizaciones_asesor_tipo_borrador_index ON cotizaciones (asesor_id, tipo, es_borrador)');
        }
        
        // 2. prenda_tallas_cot - Índice para joins con prendas (FALTANTE)
        if (!$this->indexExists('prenda_tallas_cot', 'prenda_tallas_cot_prenda_id_index')) {
            DB::statement('CREATE INDEX prenda_tallas_cot_prenda_id_index ON prenda_tallas_cot (prenda_cot_id)');
        }
        
        // 3. prenda_variantes_cot - Índice para joins con prendas (FALTANTE)
        if (!$this->indexExists('prenda_variantes_cot', 'prenda_variantes_prenda_id_index')) {
            DB::statement('CREATE INDEX prenda_variantes_prenda_id_index ON prenda_variantes_cot (prenda_cot_id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices en orden inverso
        DB::statement('DROP INDEX IF EXISTS prenda_variantes_prenda_id_index ON prenda_variantes_cot');
        DB::statement('DROP INDEX IF EXISTS prenda_tallas_cot_prenda_id_index ON prenda_tallas_cot');
        DB::statement('DROP INDEX IF EXISTS cotizaciones_asesor_tipo_borrador_index ON cotizaciones');
        DB::statement('DROP INDEX IF EXISTS cotizaciones_es_borrador_index ON cotizaciones');
        DB::statement('DROP INDEX IF EXISTS cotizaciones_tipo_index ON cotizaciones');
        DB::statement('DROP INDEX IF EXISTS cotizaciones_asesor_id_index ON cotizaciones');
    }

    /**
     * Verifica si un índice existe en una tabla
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
};
