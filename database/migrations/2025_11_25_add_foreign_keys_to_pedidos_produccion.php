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
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Agregar asesor_id para la asesora
            if (!Schema::hasColumn('pedidos_produccion', 'asesor_id')) {
                $table->foreignId('asesor_id')->nullable()->constrained('users')->onDelete('set null');
            }
            
            // Agregar cliente_id para referencia a tabla clientes
            if (!Schema::hasColumn('pedidos_produccion', 'cliente_id')) {
                $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            if (Schema::hasColumn('pedidos_produccion', 'asesor_id')) {
                $table->dropForeignIdFor('users', 'asesor_id');
                $table->dropColumn('asesor_id');
            }
            
            if (Schema::hasColumn('pedidos_produccion', 'cliente_id')) {
                $table->dropForeignIdFor('clientes', 'cliente_id');
                $table->dropColumn('cliente_id');
            }
        });
    }
};
