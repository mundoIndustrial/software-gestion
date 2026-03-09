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
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            $table->string('row_hash', 32)->nullable()->after('numero_pedido')->index();
            $table->string('genero', 50)->nullable()->after('talla');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bodega_detalles_talla', function (Blueprint $table) {
            $table->dropColumn(['row_hash', 'genero']);
        });
    }
};
