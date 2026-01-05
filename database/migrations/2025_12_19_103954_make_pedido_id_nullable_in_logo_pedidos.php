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
        Schema::table('logo_pedidos', function (Blueprint $table) {
            // pedido_id es nullable para pedidos LOGO puros (sin pedidos_produccion)
            $table->unsignedBigInteger('pedido_id')->nullable()->change();
            // numero_pedido DEBE tener un valor (se genera como 00001)
            // NO hacerlo nullable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_pedidos', function (Blueprint $table) {
            // Revertir los cambios
            $table->unsignedBigInteger('pedido_id')->nullable(false)->change();
        });
    }
};
