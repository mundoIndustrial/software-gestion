<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Elimina completamente la columna user_id de la tabla pedidos_produccion
     * Ahora solo existe asesor_id
     */
    public function up(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Solo dropear la columna si existe
            if (Schema::hasColumn('pedidos_produccion', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Recrear la columna y la foreign key en caso de rollback
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
        });
    }
};
