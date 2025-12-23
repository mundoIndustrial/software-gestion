<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega columna 'cantidad' para guardar la sumatoria de cantidades de tallas
     */
    public function up(): void
    {
        Schema::table('logo_pedidos', function (Blueprint $table) {
            // Cantidad total (suma de todas las tallas)
            $table->integer('cantidad')->default(0)->after('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_pedidos', function (Blueprint $table) {
            $table->dropColumn('cantidad');
        });
    }
};
