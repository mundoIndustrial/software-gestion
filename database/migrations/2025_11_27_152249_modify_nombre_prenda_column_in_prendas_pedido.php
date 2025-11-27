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
        // Limpiar tabla para evitar truncamiento de datos
        DB::table('prendas_pedido')->truncate();
        
        Schema::table('prendas_pedido', function (Blueprint $table) {
            $table->longText('nombre_prenda')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_pedido', function (Blueprint $table) {
            $table->string('nombre_prenda', 255)->change();
        });
    }
};
