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
            // Agregar columna marcar_plooter
            if (!Schema::hasColumn('consecutivos_recibos_pedidos', 'marcar_plooter')) {
                $table->boolean('marcar_plooter')->default(false)->after('activo')
                    ->comment('Indica si la fila está marcada en el plotter');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('consecutivos_recibos_pedidos', 'marcar_plooter')) {
                $table->dropColumn('marcar_plooter');
            }
        });
    }
};
