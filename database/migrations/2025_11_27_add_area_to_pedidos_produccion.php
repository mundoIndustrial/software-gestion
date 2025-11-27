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
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            // Agregar campo area después de estado si no existe
            if (!Schema::hasColumn('pedidos_produccion', 'area')) {
                $table->string('area')->nullable()->default('Creación Orden')->after('estado');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos_produccion', function (Blueprint $table) {
            if (Schema::hasColumn('pedidos_produccion', 'area')) {
                $table->dropColumn('area');
            }
        });
    }
};
