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
        Schema::table('prendas_pedido', function (Blueprint $table) {
            // Cambiar genero de string a longtext para almacenar JSON con múltiples valores
            if (Schema::hasColumn('prendas_pedido', 'genero')) {
                $table->longText('genero')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prendas_pedido', function (Blueprint $table) {
            if (Schema::hasColumn('prendas_pedido', 'genero')) {
                $table->string('genero')->nullable()->change();
            }
        });
    }
};
