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
        Schema::table('prendas_pedido', function (Blueprint $table) {
            // Agregar campos para variaciones
            $table->foreignId('color_id')->nullable()->constrained('colores_prenda')->onDelete('set null')->after('descripcion');
            $table->foreignId('tela_id')->nullable()->constrained('telas_prenda')->onDelete('set null')->after('color_id');
            $table->foreignId('tipo_manga_id')->nullable()->constrained('tipos_manga')->onDelete('set null')->after('tela_id');
            $table->foreignId('tipo_broche_id')->nullable()->constrained('tipos_broche')->onDelete('set null')->after('tipo_manga_id');
            $table->boolean('tiene_bolsillos')->default(false)->after('tipo_broche_id');
            $table->boolean('tiene_reflectivo')->default(false)->after('tiene_bolsillos');
            
            // Campo para observaciones de variaciones
            $table->longText('descripcion_variaciones')->nullable()->after('tiene_reflectivo');
            
            // Campo para cantidad por talla (JSON)
            $table->json('cantidad_talla')->nullable()->after('descripcion_variaciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_pedido', function (Blueprint $table) {
            $table->dropForeignKeyIfExists(['color_id']);
            $table->dropForeignKeyIfExists(['tela_id']);
            $table->dropForeignKeyIfExists(['tipo_manga_id']);
            $table->dropForeignKeyIfExists(['tipo_broche_id']);
            $table->dropColumn([
                'color_id',
                'tela_id',
                'tipo_manga_id',
                'tipo_broche_id',
                'tiene_bolsillos',
                'tiene_reflectivo',
                'descripcion_variaciones',
                'cantidad_talla'
            ]);
        });
    }
};
