<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * REFACTORIZACIÓN: Normalizar tabla prendas_pedido existente
     */
    public function up(): void
    {
        try {
            // 1. Verificar si pedido_produccion_id ya existe, si no, agregarlo
            if (!Schema::hasColumn('prendas_pedido', 'pedido_produccion_id')) {
                Schema::table('prendas_pedido', function (Blueprint $table) {
                    $table->unsignedBigInteger('pedido_produccion_id')->nullable()->after('id');
                });
                \Log::info('✅ [Migración] Columna pedido_produccion_id agregada');
            } else {
                \Log::info('ℹ️ [Migración] Columna pedido_produccion_id ya existe');
            }

            // 2. Verificar si número_pedido aún existe (podría estar siendo usado)
            if (Schema::hasColumn('prendas_pedido', 'numero_pedido')) {
                // Migrar datos: numero_pedido → pedido_produccion_id (si es necesario)
                $needsDataMigration = DB::table('prendas_pedido')
                    ->whereNotNull('numero_pedido')
                    ->whereNull('pedido_produccion_id')
                    ->exists();

                if ($needsDataMigration) {
                    DB::statement('
                        UPDATE prendas_pedido pp
                        SET pp.pedido_produccion_id = (
                            SELECT pprod.id 
                            FROM pedidos_produccion pprod 
                            WHERE pprod.numero_pedido = pp.numero_pedido 
                            LIMIT 1
                        )
                        WHERE pp.numero_pedido IS NOT NULL AND pp.pedido_produccion_id IS NULL
                    ');
                    \Log::info('✅ [Migración] Datos migrados de numero_pedido a pedido_produccion_id');
                } else {
                    \Log::info('ℹ️ [Migración] Datos ya migrados o numero_pedido vacío');
                }

                // Eliminar FK existente que apunta a numero_pedido
                try {
                    DB::statement('ALTER TABLE prendas_pedido DROP FOREIGN KEY fk_prendas_numero_pedido');
                    \Log::info('✅ [Migración] FK fk_prendas_numero_pedido eliminada');
                } catch (\Exception $e) {
                    \Log::info('ℹ️ [Migración] FK fk_prendas_numero_pedido no existe o ya fue eliminada');
                }

                // Eliminar columna numero_pedido
                Schema::table('prendas_pedido', function (Blueprint $table) {
                    $table->dropColumn('numero_pedido');
                });
                \Log::info('✅ [Migración] Columna numero_pedido eliminada');
            } else {
                \Log::info('ℹ️ [Migración] Columna numero_pedido ya fue eliminada');
            }

            // 3. Actualizar pedido_produccion_id a NOT NULL
            DB::statement('ALTER TABLE prendas_pedido MODIFY COLUMN pedido_produccion_id BIGINT UNSIGNED NOT NULL');
            
            // 4. Agregar nueva FK si no existe
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS 
                WHERE TABLE_NAME = 'prendas_pedido' 
                AND REFERENCED_TABLE_NAME = 'pedidos_produccion'
            ");
            
            if (empty($constraints)) {
                Schema::table('prendas_pedido', function (Blueprint $table) {
                    $table->foreign('pedido_produccion_id')
                        ->references('id')
                        ->on('pedidos_produccion')
                        ->onDelete('cascade');
                });
                \Log::info('✅ [Migración] Nueva FK agregada en pedido_produccion_id');
            } else {
                \Log::info('ℹ️ [Migración] FK en pedido_produccion_id ya existe');
            }

            // 5. Eliminar FKs de campos de variantes PRIMERO
            try {
                DB::statement('ALTER TABLE prendas_pedido DROP FOREIGN KEY prendas_pedido_color_id_foreign');
            } catch (\Exception $e) {}
            
            try {
                DB::statement('ALTER TABLE prendas_pedido DROP FOREIGN KEY prendas_pedido_tela_id_foreign');
            } catch (\Exception $e) {}
            
            try {
                DB::statement('ALTER TABLE prendas_pedido DROP FOREIGN KEY prendas_pedido_tipo_manga_id_foreign');
            } catch (\Exception $e) {}
            
            try {
                DB::statement('ALTER TABLE prendas_pedido DROP FOREIGN KEY prendas_pedido_tipo_broche_id_foreign');
            } catch (\Exception $e) {}
            
            // 6. Eliminar campos de variantes
            $fieldsToDropVariantes = [
                'color_id',
                'tela_id',
                'tipo_manga_id',
                'tipo_broche_id',
                'tiene_bolsillos',
                'manga_obs',
                'bolsillos_obs',
                'broche_obs',
            ];

            $columnsToDropVariantes = [];
            foreach ($fieldsToDropVariantes as $field) {
                if (Schema::hasColumn('prendas_pedido', $field)) {
                    $columnsToDropVariantes[] = $field;
                }
            }

            if (!empty($columnsToDropVariantes)) {
                Schema::table('prendas_pedido', function (Blueprint $table) use ($columnsToDropVariantes) {
                    $table->dropColumn($columnsToDropVariantes);
                });
                \Log::info('✅ [Migración] Columnas de variantes eliminadas: ' . implode(', ', $columnsToDropVariantes));
            } else {
                \Log::info('ℹ️ [Migración] Columnas de variantes ya fueron eliminadas');
            }

            // 7. Eliminar campos de reflectivo
            $fieldsToDropReflectivo = [
                'tiene_reflectivo',
                'reflectivo_obs',
            ];

            $columnsToDropReflectivo = [];
            foreach ($fieldsToDropReflectivo as $field) {
                if (Schema::hasColumn('prendas_pedido', $field)) {
                    $columnsToDropReflectivo[] = $field;
                }
            }

            if (!empty($columnsToDropReflectivo)) {
                Schema::table('prendas_pedido', function (Blueprint $table) use ($columnsToDropReflectivo) {
                    $table->dropColumn($columnsToDropReflectivo);
                });
                \Log::info('✅ [Migración] Columnas de reflectivo eliminadas: ' . implode(', ', $columnsToDropReflectivo));
            } else {
                \Log::info('ℹ️ [Migración] Columnas de reflectivo ya fueron eliminadas');
            }

            // 8. Eliminar campos redundantes
            $fieldsToDropRedundantes = [
                'cantidad',
                'descripcion_variaciones',
            ];

            $columnsToDropRedundantes = [];
            foreach ($fieldsToDropRedundantes as $field) {
                if (Schema::hasColumn('prendas_pedido', $field)) {
                    $columnsToDropRedundantes[] = $field;
                }
            }

            if (!empty($columnsToDropRedundantes)) {
                Schema::table('prendas_pedido', function (Blueprint $table) use ($columnsToDropRedundantes) {
                    $table->dropColumn($columnsToDropRedundantes);
                });
                \Log::info('✅ [Migración] Columnas redundantes eliminadas: ' . implode(', ', $columnsToDropRedundantes));
            } else {
                \Log::info('ℹ️ [Migración] Columnas redundantes ya fueron eliminadas');
            }

            \Log::info('✅ [Migración] Tabla prendas_pedido normalizada exitosamente');
        } catch (\Exception $e) {
            \Log::error('❌ [Migración] Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: Restaurar estructura original
        Schema::table('prendas_pedido', function (Blueprint $table) {
            $table->integer('numero_pedido')->unsigned()->nullable()->after('id');
            $table->integer('cantidad')->unsigned()->nullable()->after('nombre_prenda');
            $table->longText('descripcion_variaciones')->nullable();
            $table->unsignedBigInteger('color_id')->nullable();
            $table->unsignedBigInteger('tela_id')->nullable();
            $table->unsignedBigInteger('tipo_manga_id')->nullable();
            $table->unsignedBigInteger('tipo_broche_id')->nullable();
            $table->boolean('tiene_bolsillos')->default(false);
            $table->longText('manga_obs')->nullable();
            $table->longText('bolsillos_obs')->nullable();
            $table->longText('broche_obs')->nullable();
            $table->boolean('tiene_reflectivo')->default(false);
            $table->longText('reflectivo_obs')->nullable();
            $table->dropForeign(['pedido_produccion_id']);
            $table->dropColumn('pedido_produccion_id');
        });
    }
};
