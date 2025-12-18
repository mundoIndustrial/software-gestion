<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agrega índices para mejorar el rendimiento de consultas en cotizaciones reflectivo
     */
    public function up(): void
    {
        // Índices para tabla prendas_cot
        Schema::table('prendas_cot', function (Blueprint $table) {
            // Índice en cotizacion_id (usado en joins y where)
            if (!$this->indexExists('prendas_cot', 'prendas_cot_cotizacion_id_index')) {
                $table->index('cotizacion_id', 'prendas_cot_cotizacion_id_index');
            }
        });

        // Índices para tabla reflectivo_cotizacion
        Schema::table('reflectivo_cotizacion', function (Blueprint $table) {
            // Índice en cotizacion_id
            if (!$this->indexExists('reflectivo_cotizacion', 'reflectivo_cotizacion_cotizacion_id_index')) {
                $table->index('cotizacion_id', 'reflectivo_cotizacion_cotizacion_id_index');
            }
            
            // Índice en prenda_cot_id (usado en joins con prendas)
            if (!$this->indexExists('reflectivo_cotizacion', 'reflectivo_cotizacion_prenda_cot_id_index')) {
                $table->index('prenda_cot_id', 'reflectivo_cotizacion_prenda_cot_id_index');
            }
            
            // Índice compuesto para búsquedas por cotización y prenda
            if (!$this->indexExists('reflectivo_cotizacion', 'reflectivo_cotizacion_cot_prenda_index')) {
                $table->index(['cotizacion_id', 'prenda_cot_id'], 'reflectivo_cotizacion_cot_prenda_index');
            }
        });

        // Índices para tabla reflectivo_fotos_cotizacion
        Schema::table('reflectivo_fotos_cotizacion', function (Blueprint $table) {
            // Índice en reflectivo_cotizacion_id (usado en joins)
            if (!$this->indexExists('reflectivo_fotos_cotizacion', 'reflectivo_fotos_reflectivo_id_index')) {
                $table->index('reflectivo_cotizacion_id', 'reflectivo_fotos_reflectivo_id_index');
            }
        });

        // Índices para tabla prenda_fotos_cot
        Schema::table('prenda_fotos_cot', function (Blueprint $table) {
            // Índice en prenda_cot_id
            if (!$this->indexExists('prenda_fotos_cot', 'prenda_fotos_cot_prenda_id_index')) {
                $table->index('prenda_cot_id', 'prenda_fotos_cot_prenda_id_index');
            }
        });

        // Índices para tabla prenda_tela_fotos_cot
        Schema::table('prenda_tela_fotos_cot', function (Blueprint $table) {
            // Índice en prenda_cot_id
            if (!$this->indexExists('prenda_tela_fotos_cot', 'prenda_tela_fotos_prenda_id_index')) {
                $table->index('prenda_cot_id', 'prenda_tela_fotos_prenda_id_index');
            }
        });

        // Índices para tabla talla_prenda_cot
        Schema::table('talla_prenda_cot', function (Blueprint $table) {
            // Índice en prenda_cot_id
            if (!$this->indexExists('talla_prenda_cot', 'talla_prenda_cot_prenda_id_index')) {
                $table->index('prenda_cot_id', 'talla_prenda_cot_prenda_id_index');
            }
        });

        // Índices para tabla prenda_variantes_cot
        Schema::table('prenda_variantes_cot', function (Blueprint $table) {
            // Índice en prenda_cot_id
            if (!$this->indexExists('prenda_variantes_cot', 'prenda_variantes_prenda_id_index')) {
                $table->index('prenda_cot_id', 'prenda_variantes_prenda_id_index');
            }
        });

        // Índices para tabla cotizaciones (tabla principal)
        Schema::table('cotizaciones', function (Blueprint $table) {
            // Índice en asesor_id (para filtrar por asesor)
            if (!$this->indexExists('cotizaciones', 'cotizaciones_asesor_id_index')) {
                $table->index('asesor_id', 'cotizaciones_asesor_id_index');
            }
            
            // Índice en tipo (para filtrar por tipo RF, PL, etc)
            if (!$this->indexExists('cotizaciones', 'cotizaciones_tipo_index')) {
                $table->index('tipo', 'cotizaciones_tipo_index');
            }
            
            // Índice en es_borrador (para filtrar borradores)
            if (!$this->indexExists('cotizaciones', 'cotizaciones_es_borrador_index')) {
                $table->index('es_borrador', 'cotizaciones_es_borrador_index');
            }
            
            // Índice compuesto para búsquedas comunes
            if (!$this->indexExists('cotizaciones', 'cotizaciones_asesor_tipo_borrador_index')) {
                $table->index(['asesor_id', 'tipo', 'es_borrador'], 'cotizaciones_asesor_tipo_borrador_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices en orden inverso
        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropIndex('cotizaciones_asesor_tipo_borrador_index');
            $table->dropIndex('cotizaciones_es_borrador_index');
            $table->dropIndex('cotizaciones_tipo_index');
            $table->dropIndex('cotizaciones_asesor_id_index');
        });

        Schema::table('prenda_variantes_cot', function (Blueprint $table) {
            $table->dropIndex('prenda_variantes_prenda_id_index');
        });

        Schema::table('talla_prenda_cot', function (Blueprint $table) {
            $table->dropIndex('talla_prenda_cot_prenda_id_index');
        });

        Schema::table('prenda_tela_fotos_cot', function (Blueprint $table) {
            $table->dropIndex('prenda_tela_fotos_prenda_id_index');
        });

        Schema::table('prenda_fotos_cot', function (Blueprint $table) {
            $table->dropIndex('prenda_fotos_cot_prenda_id_index');
        });

        Schema::table('reflectivo_fotos_cotizacion', function (Blueprint $table) {
            $table->dropIndex('reflectivo_fotos_reflectivo_id_index');
        });

        Schema::table('reflectivo_cotizacion', function (Blueprint $table) {
            $table->dropIndex('reflectivo_cotizacion_cot_prenda_index');
            $table->dropIndex('reflectivo_cotizacion_prenda_cot_id_index');
            $table->dropIndex('reflectivo_cotizacion_cotizacion_id_index');
        });

        Schema::table('prendas_cot', function (Blueprint $table) {
            $table->dropIndex('prendas_cot_cotizacion_id_index');
        });
    }

    /**
     * Verifica si un índice existe en una tabla
     */
    private function indexExists(string $table, string $index): bool
    {
        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);
        return count($indexes) > 0;
    }
};
