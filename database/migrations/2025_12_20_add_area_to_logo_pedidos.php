<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega el campo area a la tabla logo_pedidos
     */
    public function up(): void
    {
        Schema::table('logo_pedidos', function (Blueprint $table) {
            // Campo para el área (ej: 'creacion_de_orden')
            $table->string('area')->default('creacion_de_orden')->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logo_pedidos', function (Blueprint $table) {
            $table->dropColumn('area');
        });
    }
};
