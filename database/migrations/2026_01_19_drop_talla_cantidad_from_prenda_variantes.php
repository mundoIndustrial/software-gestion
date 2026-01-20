<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * ELIMINAR COLUMNAS: talla y cantidad
     * 
     * Razón:
     * - Las tallas ahora se guardan en JSON en prendas_pedido.cantidad_talla
     * - Las cantidades se extraen del JSON según la combinación de variante
     * - Simplifica la estructura y evita duplicación de datos
     * 
     * Cambios:
     * 1. Eliminar columna 'talla' (ya está en prenda padre)
     * 2. Eliminar columna 'cantidad' (ya está en prenda padre como JSON)
     * 3. Eliminar índice UNIQUE único (incompatible sin talla)
     * 4. Crear índice regular en prenda_pedido_id (ya existe, mantener)
     */
    public function up(): void
    {
        // Eliminar índices (ignorar si no existen)
        try {
            \DB::statement("ALTER TABLE prenda_pedido_variantes DROP INDEX unique_prenda_talla");
        } catch (\Exception $e) {
            \Log::debug('[Migración] unique_prenda_talla no encontrado');
        }
        
        try {
            \DB::statement("ALTER TABLE prenda_pedido_variantes DROP INDEX unique_prenda_variante");
        } catch (\Exception $e) {
            \Log::debug('[Migración] unique_prenda_variante no encontrado');
        }
        
        try {
            \DB::statement("ALTER TABLE prenda_pedido_variantes DROP INDEX prenda_pedido_variantes_talla_index");
        } catch (\Exception $e) {
            \Log::debug('[Migración] prenda_pedido_variantes_talla_index no encontrado');
        }
        
        // Eliminar columnas
        \DB::statement("ALTER TABLE prenda_pedido_variantes DROP COLUMN talla, DROP COLUMN cantidad");

        \Log::info(' [Migración 2026_01_19] Columnas talla y cantidad eliminadas de prenda_pedido_variantes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_pedido_variantes', function (Blueprint $table) {
            // Restaurar columnas
            $table->string('talla', 50)->nullable()->after('prenda_pedido_id');
            $table->unsignedInteger('cantidad')->default(0)->nullable()->after('talla');
            
            // Restaurar índices
            $table->index('talla');
            $table->unique(
                ['prenda_pedido_id', 'talla'],
                'unique_prenda_variante'
            );
        });

        \Log::info('⏮️  [Migración 2026_01_19] Rollback - Columnas talla y cantidad restauradas');
    }
};
