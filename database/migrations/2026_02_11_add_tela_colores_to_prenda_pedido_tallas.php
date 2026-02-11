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
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            // Agregar columnas para tela y colores asignados
            if (!Schema::hasColumn('prenda_pedido_tallas', 'tela')) {
                $table->string('tela', 100)->nullable()->after('cantidad')->comment('Nombre de la tela asignada (ej: ATLETIC, POLIÉSTER)');
            }
            
            if (!Schema::hasColumn('prenda_pedido_tallas', 'colores')) {
                $table->json('colores')->nullable()->after('tela')->comment('JSON de colores asignados [{nombre: "ARENA", cantidad: 1}]');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_pedido_tallas', function (Blueprint $table) {
            if (Schema::hasColumn('prenda_pedido_tallas', 'colores')) {
                $table->dropColumn('colores');
            }
            
            if (Schema::hasColumn('prenda_pedido_tallas', 'tela')) {
                $table->dropColumn('tela');
            }
        });
    }
};
