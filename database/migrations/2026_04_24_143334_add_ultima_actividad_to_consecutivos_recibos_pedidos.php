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
            $table->timestamp('ultima_actividad')->nullable()->after('updated_at');
        });

        // Poblar registros existentes con la fecha más reciente
        \DB::statement('UPDATE consecutivos_recibos_pedidos
                       SET ultima_actividad = COALESCE(updated_at, created_at)
                       WHERE id > 0 AND ultima_actividad IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consecutivos_recibos_pedidos', function (Blueprint $table) {
            $table->dropColumn('ultima_actividad');
        });
    }
};
