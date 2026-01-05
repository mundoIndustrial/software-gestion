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
     * Agrega el campo numero_pedido_cost para guardar el número de pedido
     * de costura asociado (de pedidos_produccion)
     */
    public function up(): void
    {
        Schema::table('logo_pedidos', function (Blueprint $table) {
            $table->integer('numero_pedido_cost')->nullable()->after('pedido_id');
        });
        
        // Actualizar los registros existentes con el numero_pedido de pedidos_produccion
        DB::statement("
            UPDATE logo_pedidos lp
            INNER JOIN pedidos_produccion pp ON lp.pedido_id = pp.id
            SET lp.numero_pedido_cost = pp.numero_pedido
            WHERE lp.pedido_id IS NOT NULL
        ");
        
        \Log::info('✅ Campo numero_pedido_cost agregado y actualizado');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_pedidos', function (Blueprint $table) {
            $table->dropColumn('numero_pedido_cost');
        });
    }
};
