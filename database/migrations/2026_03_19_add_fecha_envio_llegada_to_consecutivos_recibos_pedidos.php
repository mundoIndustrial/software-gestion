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
            // Agregar columnas de fecha_envio y fecha_llegada
            if (!Schema::hasColumn('consecutivos_recibos_pedidos', 'fecha_envio')) {
                $table->dateTime('fecha_envio')->nullable()->after('marcar_plooter')
                    ->comment('Fecha cuando se marcó el checkbox para envío');
            }
            if (!Schema::hasColumn('consecutivos_recibos_pedidos', 'fecha_llegada')) {
                $table->dateTime('fecha_llegada')->nullable()->after('fecha_envio')
                    ->comment('Fecha de llegada del recibo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('consecutivos_recibos_pedidos', 'fecha_envio')) {
                $table->dropColumn('fecha_envio');
            }
            if (Schema::hasColumn('consecutivos_recibos_pedidos', 'fecha_llegada')) {
                $table->dropColumn('fecha_llegada');
            }
        });
    }
};
