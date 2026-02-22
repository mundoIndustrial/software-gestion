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
            $table->string('estado', 50)->default('PENDIENTE_INSUMOS')->after('activo');
        });

        // Actualizar registros existentes: si el pedido ya fue aprobado (En Ejecución/No iniciado), 
        // reflejar ese estado en el recibo también
        DB::statement("
            UPDATE consecutivos_recibos_pedidos crp
            INNER JOIN pedidos_produccion pp ON pp.id = crp.pedido_produccion_id
            SET crp.estado = pp.estado
            WHERE pp.estado IN ('No iniciado', 'En Ejecución')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
