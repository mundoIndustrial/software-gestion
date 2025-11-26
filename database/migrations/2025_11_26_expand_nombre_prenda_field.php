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
        // Expandir el campo nombre_prenda a TEXT para permitir descripciones muy largas
        Schema::table('prendas_pedido', function (Blueprint $table) {
            $table->text('nombre_prenda')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_pedido', function (Blueprint $table) {
            $table->string('nombre_prenda', 100)->change();
        });
    }
};
