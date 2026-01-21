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
        Schema::table('prenda_pedido_variantes', function (Blueprint $table) {
            $table->dropColumn('tiene_bolsillos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prenda_pedido_variantes', function (Blueprint $table) {
            $table->boolean('tiene_bolsillos')->default(false)->after('broche_boton_obs');
        });
    }
};
