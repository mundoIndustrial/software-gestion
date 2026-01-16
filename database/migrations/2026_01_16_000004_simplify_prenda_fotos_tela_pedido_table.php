<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Simplificar tabla prenda_fotos_tela_pedido
     * Solo guardar: ruta_original, ruta_webp, prenda_pedido_id, tela_id, color_id, orden, observaciones
     * Eliminar: ruta_miniatura, ancho, alto, tamaño
     */
    public function up(): void
    {
        Schema::table('prenda_fotos_tela_pedido', function (Blueprint $table) {
            // Eliminar campos innecesarios
            $columnsToRemove = [];
            
            if (Schema::hasColumn('prenda_fotos_tela_pedido', 'ruta_miniatura')) {
                $columnsToRemove[] = 'ruta_miniatura';
            }
            if (Schema::hasColumn('prenda_fotos_tela_pedido', 'ancho')) {
                $columnsToRemove[] = 'ancho';
            }
            if (Schema::hasColumn('prenda_fotos_tela_pedido', 'alto')) {
                $columnsToRemove[] = 'alto';
            }
            if (Schema::hasColumn('prenda_fotos_tela_pedido', 'tamaño')) {
                $columnsToRemove[] = 'tamaño';
            }
            
            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_fotos_tela_pedido', function (Blueprint $table) {
            $table->string('ruta_miniatura', 255)->nullable()->after('ruta_webp');
            $table->integer('ancho')->nullable()->after('ruta_miniatura');
            $table->integer('alto')->nullable()->after('ancho');
            $table->integer('tamaño')->nullable()->after('alto');
        });
    }
};
