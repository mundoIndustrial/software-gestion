<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            $table->string('area', 100)->default('Insumos')->after('estado');
        });

        // Sincronizar area desde el pedido padre para registros ya aprobados
        DB::statement("
            UPDATE consecutivos_recibos_pedidos crp
            INNER JOIN pedidos_produccion pp ON pp.id = crp.pedido_produccion_id
            SET crp.area = pp.area
            WHERE crp.estado != 'PENDIENTE_INSUMOS'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            $table->dropColumn('area');
        });
    }
};
