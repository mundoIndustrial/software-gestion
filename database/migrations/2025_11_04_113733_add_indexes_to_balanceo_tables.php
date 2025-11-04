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
        Schema::table('prendas', function (Blueprint $table) {
            $table->index('activo', 'idx_prendas_activo');
            $table->index(['activo', 'created_at'], 'idx_prendas_activo_created');
            $table->index('nombre', 'idx_prendas_nombre');
            $table->index('referencia', 'idx_prendas_referencia');
            $table->index('tipo', 'idx_prendas_tipo');
        });

        Schema::table('balanceos', function (Blueprint $table) {
            $table->index(['prenda_id', 'activo'], 'idx_balanceos_prenda_activo');
            $table->index('activo', 'idx_balanceos_activo');
        });

        Schema::table('operaciones_balanceo', function (Blueprint $table) {
            $table->index('balanceo_id', 'idx_operaciones_balanceo_id');
            $table->index(['balanceo_id', 'orden'], 'idx_operaciones_balanceo_orden');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas', function (Blueprint $table) {
            $table->dropIndex('idx_prendas_activo');
            $table->dropIndex('idx_prendas_activo_created');
            $table->dropIndex('idx_prendas_nombre');
            $table->dropIndex('idx_prendas_referencia');
            $table->dropIndex('idx_prendas_tipo');
        });

        Schema::table('balanceos', function (Blueprint $table) {
            $table->dropIndex('idx_balanceos_prenda_activo');
            $table->dropIndex('idx_balanceos_activo');
        });

        Schema::table('operaciones_balanceo', function (Blueprint $table) {
            $table->dropIndex('idx_operaciones_balanceo_id');
            $table->dropIndex('idx_operaciones_balanceo_orden');
        });
    }
};
