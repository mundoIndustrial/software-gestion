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
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            // Remover columnas si existen (ya que irán en la tabla plooter)
            if (Schema::hasColumn('consecutivos_recibos_pedidos', 'fecha_envio')) {
                $table->dropColumn('fecha_envio');
            }
            if (Schema::hasColumn('consecutivos_recibos_pedidos', 'fecha_llegada')) {
                $table->dropColumn('fecha_llegada');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            // Restaurar columnas si es necesario hacer rollback
            if (!Schema::hasColumn('consecutivos_recibos_pedidos', 'fecha_envio')) {
                $table->dateTime('fecha_envio')->nullable()->after('marcar_plooter');
            }
            if (!Schema::hasColumn('consecutivos_recibos_pedidos', 'fecha_llegada')) {
                $table->dateTime('fecha_llegada')->nullable()->after('fecha_envio');
            }
        });
    }
};
