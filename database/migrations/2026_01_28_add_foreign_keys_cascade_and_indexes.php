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
     * Agregar Foreign Keys con ON DELETE CASCADE
     * Agregar índices para queries frecuentes
     */
    public function up(): void
    {
        // ====================================
        // 1. PROCESOS: Prenda → Procesos
        // ====================================
        if (Schema::hasTable('pedidos_procesos_prenda_detalles') && Schema::hasTable('prenda_pedido')) {
            Schema::table('pedidos_procesos_prenda_detalles', function (Blueprint $table) {
                // Verificar si FK ya existe antes de agregar
                $keyExists = DB::selectOne("
                    SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'pedidos_procesos_prenda_detalles' 
                    AND COLUMN_NAME = 'prenda_pedido_id' 
                    AND REFERENCED_TABLE_NAME = 'prenda_pedido'
                ") !== null;

                if (!$keyExists) {
                    $table->foreign('prenda_pedido_id')
                        ->references('id')
                        ->on('prenda_pedido')
                        ->onDelete('cascade');  // ← Si se borra prenda, borrar procesos
                }

                // Índice para búsquedas por prenda
                if (!Schema::hasColumn('pedidos_procesos_prenda_detalles', 'idx_prenda_pedido_id')) {
                    $table->index('prenda_pedido_id');
                }
            });
        }

        // ====================================
        // 2. PROCESOS: Tipo → Procesos
        // ====================================
        if (Schema::hasTable('pedidos_procesos_prenda_detalles') && Schema::hasTable('tipos_procesos')) {
            Schema::table('pedidos_procesos_prenda_detalles', function (Blueprint $table) {
                $keyExists = DB::selectOne("
                    SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'pedidos_procesos_prenda_detalles' 
                    AND COLUMN_NAME = 'tipo_proceso_id' 
                    AND REFERENCED_TABLE_NAME = 'tipos_procesos'
                ") !== null;

                if (!$keyExists) {
                    $table->foreign('tipo_proceso_id')
                        ->references('id')
                        ->on('tipos_procesos')
                        ->onDelete('restrict');  // No permitir borrar tipo si hay procesos
                }

                // Índice para búsquedas por tipo
                if (!Schema::hasColumn('pedidos_procesos_prenda_detalles', 'idx_tipo_proceso_id')) {
                    $table->index('tipo_proceso_id');
                }
            });
        }

        // ====================================
        // 3. EPP: Pedido → Pedido EPP
        // ====================================
        if (Schema::hasTable('pedido_epp') && Schema::hasTable('pedido_produccion')) {
            Schema::table('pedido_epp', function (Blueprint $table) {
                $keyExists = DB::selectOne("
                    SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'pedido_epp' 
                    AND COLUMN_NAME = 'pedido_produccion_id' 
                    AND REFERENCED_TABLE_NAME = 'pedido_produccion'
                ") !== null;

                if (!$keyExists) {
                    $table->foreign('pedido_produccion_id')
                        ->references('id')
                        ->on('pedido_produccion')
                        ->onDelete('cascade');  // ← Si se borra pedido, borrar EPP
                }

                // Índice para búsquedas por pedido
                if (!Schema::hasColumn('pedido_epp', 'idx_pedido_produccion_id')) {
                    $table->index('pedido_produccion_id');
                }
            });
        }

        // ====================================
        // 4. EPP: EPP → Imagen EPP
        // ====================================
        if (Schema::hasTable('pedido_epp_imagen') && Schema::hasTable('pedido_epp')) {
            Schema::table('pedido_epp_imagen', function (Blueprint $table) {
                $keyExists = DB::selectOne("
                    SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'pedido_epp_imagen' 
                    AND COLUMN_NAME = 'pedido_epp_id' 
                    AND REFERENCED_TABLE_NAME = 'pedido_epp'
                ") !== null;

                if (!$keyExists) {
                    $table->foreign('pedido_epp_id')
                        ->references('id')
                        ->on('pedido_epp')
                        ->onDelete('cascade');  // ← Si se borra EPP, borrar imágenes
                }

                // Índice para búsquedas
                if (!Schema::hasColumn('pedido_epp_imagen', 'idx_pedido_epp_id')) {
                    $table->index('pedido_epp_id');
                }
            });
        }

        // ====================================
        // 5. PRENDAS: Pedido → Prenda
        // ====================================
        if (Schema::hasTable('prenda_pedido') && Schema::hasTable('pedido_produccion')) {
            Schema::table('prenda_pedido', function (Blueprint $table) {
                $keyExists = DB::selectOne("
                    SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'prenda_pedido' 
                    AND COLUMN_NAME = 'pedido_produccion_id' 
                    AND REFERENCED_TABLE_NAME = 'pedido_produccion'
                ") !== null;

                if (!$keyExists) {
                    $table->foreign('pedido_produccion_id')
                        ->references('id')
                        ->on('pedido_produccion')
                        ->onDelete('cascade');  // ← Si se borra pedido, borrar prendas
                }

                // Índice para búsquedas
                if (!Schema::hasColumn('prenda_pedido', 'idx_pedido_produccion_id')) {
                    $table->index('pedido_produccion_id');
                }
            });
        }

        // ====================================
        // 6. FOTOS: Prenda → Foto Prenda
        // ====================================
        if (Schema::hasTable('prenda_fotos_pedido') && Schema::hasTable('prenda_pedido')) {
            Schema::table('prenda_fotos_pedido', function (Blueprint $table) {
                $keyExists = DB::selectOne("
                    SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'prenda_fotos_pedido' 
                    AND COLUMN_NAME = 'prenda_pedido_id' 
                    AND REFERENCED_TABLE_NAME = 'prenda_pedido'
                ") !== null;

                if (!$keyExists) {
                    $table->foreign('prenda_pedido_id')
                        ->references('id')
                        ->on('prenda_pedido')
                        ->onDelete('cascade');
                }

                if (!Schema::hasColumn('prenda_fotos_pedido', 'idx_prenda_pedido_id')) {
                    $table->index('prenda_pedido_id');
                }
            });
        }

        // ====================================
        // 7. FOTOS TELA: Tela → Foto Tela
        // ====================================
        if (Schema::hasTable('prenda_foto_tela_pedido') && Schema::hasTable('prenda_pedido_colores_telas')) {
            Schema::table('prenda_foto_tela_pedido', function (Blueprint $table) {
                $keyExists = DB::selectOne("
                    SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'prenda_foto_tela_pedido' 
                    AND COLUMN_NAME = 'prenda_pedido_colores_telas_id' 
                    AND REFERENCED_TABLE_NAME = 'prenda_pedido_colores_telas'
                ") !== null;

                if (!$keyExists) {
                    $table->foreign('prenda_pedido_colores_telas_id')
                        ->references('id')
                        ->on('prenda_pedido_colores_telas')
                        ->onDelete('cascade');
                }

                if (!Schema::hasColumn('prenda_foto_tela_pedido', 'idx_tela_id')) {
                    $table->index('prenda_pedido_colores_telas_id');
                }
            });
        }

        // ====================================
        // 8. VARIANTES: Prenda → Variante
        // ====================================
        if (Schema::hasTable('prenda_pedido_variantes') && Schema::hasTable('prenda_pedido')) {
            Schema::table('prenda_pedido_variantes', function (Blueprint $table) {
                $keyExists = DB::selectOne("
                    SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'prenda_pedido_variantes' 
                    AND COLUMN_NAME = 'prenda_pedido_id' 
                    AND REFERENCED_TABLE_NAME = 'prenda_pedido'
                ") !== null;

                if (!$keyExists) {
                    $table->foreign('prenda_pedido_id')
                        ->references('id')
                        ->on('prenda_pedido')
                        ->onDelete('cascade');
                }

                if (!Schema::hasColumn('prenda_pedido_variantes', 'idx_prenda_pedido_id')) {
                    $table->index('prenda_pedido_id');
                }
            });
        }

        // ====================================
        // 9. TALLAS: Prenda → Talla
        // ====================================
        if (Schema::hasTable('prenda_pedido_tallas') && Schema::hasTable('prenda_pedido')) {
            Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
                $keyExists = DB::selectOne("
                    SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'prenda_pedido_tallas' 
                    AND COLUMN_NAME = 'prenda_pedido_id' 
                    AND REFERENCED_TABLE_NAME = 'prenda_pedido'
                ") !== null;

                if (!$keyExists) {
                    $table->foreign('prenda_pedido_id')
                        ->references('id')
                        ->on('prenda_pedido')
                        ->onDelete('cascade');
                }

                if (!Schema::hasColumn('prenda_pedido_tallas', 'idx_prenda_pedido_id')) {
                    $table->index('prenda_pedido_id');
                }
            });
        }

        // ====================================
        // 10. COLORES TELAS: Prenda → Tela
        // ====================================
        if (Schema::hasTable('prenda_pedido_colores_telas') && Schema::hasTable('prenda_pedido')) {
            Schema::table('prenda_pedido_colores_telas', function (Blueprint $table) {
                $keyExists = DB::selectOne("
                    SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                    WHERE TABLE_NAME = 'prenda_pedido_colores_telas' 
                    AND COLUMN_NAME = 'prenda_pedido_id' 
                    AND REFERENCED_TABLE_NAME = 'prenda_pedido'
                ") !== null;

                if (!$keyExists) {
                    $table->foreign('prenda_pedido_id')
                        ->references('id')
                        ->on('prenda_pedido')
                        ->onDelete('cascade');
                }

                if (!Schema::hasColumn('prenda_pedido_colores_telas', 'idx_prenda_pedido_id')) {
                    $table->index('prenda_pedido_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Las migraciones de rollback se invierten automáticamente
    }
};
