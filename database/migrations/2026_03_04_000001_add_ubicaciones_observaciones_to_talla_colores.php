<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agregar campos de ubicaciones y observaciones a la tabla de colores
     * para permitir especificaciones diferentes por talla__color
     */
    public function up(): void
    {
        Schema::table('pedidos_procesos_prenda_talla_colores', function (Blueprint $table) {
            // UBICACIONES específicas para este color/talla (JSON array)
            $table->json('ubicaciones')->nullable()->after('tela_nombre')->comment('Ubicaciones específicas para este color en esta talla');
            
            // OBSERVACIONES específicas para este color/talla
            $table->text('observaciones')->nullable()->after('ubicaciones')->comment('Observaciones específicas para este color en esta talla');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_procesos_prenda_talla_colores', function (Blueprint $table) {
            $table->dropColumn(['ubicaciones', 'observaciones']);
        });
    }
};
