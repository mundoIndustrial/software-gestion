<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ⚡ Agregar índices para búsquedas rápidas en autocomplete
        
        // Índice para búsqueda de telas
        if (Schema::hasTable('telas')) {
            Schema::table('telas', function (Blueprint $table) {
                // Verificar si el índice ya existe antes de agregarlo
                if (!$this->indexExists('telas', 'idx_nombre_tela')) {
                    $table->index('nombre_tela', 'idx_nombre_tela');
                }
            });
        }
        
        // Índice para búsqueda de máquinas
        if (Schema::hasTable('maquinas')) {
            Schema::table('maquinas', function (Blueprint $table) {
                if (!$this->indexExists('maquinas', 'idx_nombre_maquina')) {
                    $table->index('nombre_maquina', 'idx_nombre_maquina');
                }
            });
        }
        
        // Índice para búsqueda de usuarios (operarios)
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!$this->indexExists('users', 'idx_name')) {
                    $table->index('name', 'idx_name');
                }
            });
        }
        
        // Índice para búsqueda de horas
        if (Schema::hasTable('horas')) {
            Schema::table('horas', function (Blueprint $table) {
                if (!$this->indexExists('horas', 'idx_hora')) {
                    $table->index('hora', 'idx_hora');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // ⚡ Eliminar índices
        
        if (Schema::hasTable('telas')) {
            Schema::table('telas', function (Blueprint $table) {
                if ($this->indexExists('telas', 'idx_nombre_tela')) {
                    $table->dropIndex('idx_nombre_tela');
                }
            });
        }
        
        if (Schema::hasTable('maquinas')) {
            Schema::table('maquinas', function (Blueprint $table) {
                if ($this->indexExists('maquinas', 'idx_nombre_maquina')) {
                    $table->dropIndex('idx_nombre_maquina');
                }
            });
        }
        
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->indexExists('users', 'idx_name')) {
                    $table->dropIndex('idx_name');
                }
            });
        }
        
        if (Schema::hasTable('horas')) {
            Schema::table('horas', function (Blueprint $table) {
                if ($this->indexExists('horas', 'idx_hora')) {
                    $table->dropIndex('idx_hora');
                }
            });
        }
    }
    
    /**
     * Verificar si un índice existe en una tabla
     */
    private function indexExists($table, $indexName): bool
    {
        $indexes = \DB::select("SHOW INDEX FROM {$table}");
        foreach ($indexes as $index) {
            if ($index->Key_name === $indexName) {
                return true;
            }
        }
        return false;
    }
};
